<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Ingresar extends CI_Controller
{

    /**
     * Index Page for this controller.
     *
     * Maps to the following URL
     *         http://example.com/index.php/welcome
     *    - or -
     *         http://example.com/index.php/welcome/index
     *    - or -
     * Since this controller is set as the default controller in
     * config/routes.php, it's displayed at http://example.com/
     *
     * So any other public methods not prefixed with an underscore will
     * map to /index.php/welcome/<method_name>
     * @see https://codeigniter.com/user_guide/general/urls.html
     */

    public function __construct()
    {
        parent::__construct();
        $this->load->model('acuerdosing/AcuerdosIngreso');
        $this->load->model('consulta/SubsanarPro');
        $this->load->model('consulta/ConsultaPro');
        $this->load->model('consulta/Proyectos');
        $this->load->model('acuerdosform/IngresoAcu');

        if (!$this->session->userdata('logueado')) {
            redirect(base_url());
        }

        if (($_SESSION['perfil']<>1) && ($_SESSION['perfil']<>5)  && ($_SESSION['perfil']<>3))
        {
                    redirect(base_url());
                }

    }

    public function index()
    {

        $this->scripts = array(
            base_url() . "assets/adlte/plugins/bs-custom-file-input/bs-custom-file-input.min.js",
            base_url() . "assets/js/jquery.mask.min.js?7=" . rand(),
            base_url() . "assets/js/ingresoacuerdo.js?7=" . rand(),

        );

        $this->csss = array(

			base_url()."assets/css/ingreso.css",
			
			);

            $datosareasdeta = $this->IngresoAcu->get_areasedeta();
            $datosarease = $this->IngresoAcu->get_arease();
            $secretarias = $this->IngresoAcu->get_secretarias();
            $data['datosareasdeta'] = $datosareasdeta;
            $data['datosarease'] = $datosarease;
            $data['secretarias'] = $secretarias;

        $data['titulo'] = "Ingreso Acuerdos  ";
         

        $this->load->view('plantilla/header', $data);
        $this->load->view('acuerdos/ppalingacuerdo', $data);
       
        $this->load->view('consultaP/modalesform', $data);
        $this->load->view('plantilla/footer');

    }


    public function salvardata()
    {
        $consecutivo=$this->input->post('consecutivo');
        $val=$this->AcuerdosIngreso->valida_acuerdoseg($consecutivo);
        if ($val['nro']>0)
        {
            $respuesta = array();
            $respuesta['estado'] = "false";
            $respuesta['mensaje'] = "El acuerdo ya esta generado como un proyecto - " . $consecutivo;
            $respuesta['numero'] = $consecutivo;
            echo json_encode($respuesta);
            die();
        }
       $dataproyecto=$_POST;
     
       $dataproyecto['usuario']=$_SESSION['usuario'];
       $dataproyecto['fecha_creacion']=date('Y-m-d');
       $dataproyecto['hora']=date('h:i:sa');
       $dataproyecto['aportegob'] = str_replace(".", "", $dataproyecto['aportegob']);
       $dataproyecto['aportemun'] = str_replace(".", "", $dataproyecto['aportemun']);
       $dataproyecto['aporteotr'] = str_replace(".", "", $dataproyecto['aporteotr']);

       unset($dataproyecto['numerogobernacion']);
       unset($dataproyecto['numeromunicipio']);
       unset($dataproyecto['numerootros']);
       unset($dataproyecto['fechahhora']);
       


       $this->db->trans_begin();

       $this->AcuerdosIngreso->ins_acuerdoseg($dataproyecto);
            if ($this->db->trans_status() === false) {
                $this->db->trans_rollback();
                $respuesta['estado'] = "false";
                $respuesta['mensaje'] = "Hubo un error creando en acuerdo, contacte al administrador ";

                echo json_encode($respuesta);
            } 
            else
            {
                $this->db->trans_commit();
                $respuesta = array();
                $respuesta['estado'] = "true";
                $respuesta['mensaje'] = "Se ha generado correctamente el acuerdo número " . $consecutivo;
                $respuesta['numero'] = $consecutivo;
                echo json_encode($respuesta);


            }




    }

    public function validaproyecto()

    {
        $consecutivo=$this->input->post('proyecto');
        
        
        if ($_SESSION['perfil']=="1"){
            $consulta=$this->AcuerdosIngreso->get_proyecto($consecutivo);	
            }
            else            
            {
                $consulta=$this->AcuerdosIngreso->get_proyecto($consecutivo);	
            }


            

       
        if (!isset($consulta['consecutivo']))
        {
            echo "error";
        }   
        else
        {
            $valida=$this->AcuerdosIngreso->get_cuentaacuerdo($consecutivo);	
            if ($valida['nro']>0)
            {
                echo "error";
            }
            else
            {
            // aca comienza el else
         
            $acuerdo = $consulta;
            $aprobar = $this->Proyectos->get_aprobados($consecutivo);
            $aportes = $this->ConsultaPro->get_aportes($consecutivo);
            $programa = $this->ConsultaPro->get_programa();
            $data['programa'] = $programa;
            $data['acuerdo'] = $acuerdo;
            $data['aprobar'] = $aprobar;
            $datosareasdeta = $this->IngresoAcu->get_areasedeta();
            $datosarease = $this->IngresoAcu->get_arease();
            $secretarias = $this->IngresoAcu->get_secretarias();
            $data['datosareasdeta'] = $datosareasdeta;
            $data['datosarease'] = $datosarease;
            $data['secretarias'] = $secretarias;
            
            $x1 = 0;
            $totdep = 0;
            $x2 = 0;
            $totmun = 0;
            $x3 = 0;
            $tototr = 0;
            for ($i = 0; $i < sizeof($aportes); $i++) {
    
                if ($aportes[$i]['tipo'] == "Departamento") {
                    $apordep[$x1]['fuente'] = $aportes[$i]['fuente'];
                    $apordep[$x1]['aporte'] = $aportes[$i]['aporte'];
                    $totdep = $totdep + $aportes[$i]['aporte'];
                    $x1 = $x1 + 1;
                }
                if ($aportes[$i]['tipo'] == "Municipio") {
                    $apormun[$x2]['fuente'] = $aportes[$i]['fuente'];
                    $apormun[$x2]['aporte'] = $aportes[$i]['aporte'];
                    $totmun = $totmun + $aportes[$i]['aporte'];
                    $x2 = $x2 + 1;
                }
                if ($aportes[$i]['tipo'] == "Otras") {
                    $aporotr[$x3]['fuente'] = $aportes[$i]['fuente'];
                    $aporotr[$x3]['aporte'] = $aportes[$i]['aporte'];
                    $tototr = $tototr + $aportes[$i]['aporte'];
                    $x3 = $x3 + 1;
                }
            }
            $maximo = max($x1, $x2, $x3);
    
            $data['acuerdo'] = $acuerdo;
            if (isset($apordep)) {
                $data['apordep'] = $apordep;
            }
            if (isset($apormun)) {
                $data['apormun'] = $apormun;
            }
            if (isset($aporotr)) {
                $data['aporotr'] = $aporotr;
            }
    
            $data['aportes'] = $aportes;
            $data['totdep'] = $totdep;
            $data['totmun'] = $totmun;
            $data['tototr'] = $tototr;
            $data['maximo'] = $maximo;
    
            $this->load->view('acuerdos/inciaracuerdo', $data);
          //  $this->load->view('consultaP/detallepro', $data);
            
            // aca termina el elese
        }
    }



    }


    public function buscarproyectoajax ()
	{
      
        $key=$this->input->post('key');
    
        if ($_SESSION['perfil']=="1"){
        $result=$this->AcuerdosIngreso->get_busca_proyecto($key);	
        }
        else            
        {
        $result=$this->AcuerdosIngreso->get_busca_proyectouser($key);	
        }


      //  $consulta =$this->indica->get_busca_indicador($key);
        

		$html="";
		if (sizeof($result)> 0) {
			for ($i=0;$i<sizeof($result);$i++) {            
				$html = $html. '<div class="" ><a class="suggest-element" data="'.($result[$i]['name']).'" id="'.$result[$i]['consecutivo'].'">'.($result[$i]['name']).'</a></div><p>';
			}
		}
		echo $html;


	}




    
    public function acuerdosiniciados()
    {
        $this->csss = array(
			base_url()."assets/adlte/plugins/datatables-bs4/css/dataTables.bootstrap4.css",
			base_url()."assets/adlte/plugins/datatables-buttons/css/buttons.bootstrap4.css",
			base_url()."assets/css/datatb.css",
			
			);


		$this->scripts = array(
		
		base_url()."assets/adlte/plugins/datatables/jquery.dataTables.min.js",
		base_url()."assets/adlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js",
		base_url()."assets/adlte/plugins/datatables-responsive/js/dataTables.responsive.min.js",
		base_url()."assets/adlte/plugins/datatables-buttons/js/dataTables.buttons.min.js",
		base_url()."assets/adlte/plugins/datatables-buttons/js/buttons.bootstrap4.min.js",
		base_url()."assets/adlte/plugins/jszip/jszip.min.js",
		base_url()."assets/adlte/plugins/pdfmake/pdfmake.js",
		base_url()."assets/adlte/plugins/pdfmake/vfs_fonts.js",
		base_url()."assets/adlte/plugins/datatables-buttons/js/buttons.html5.min.js",
        base_url()."assets/adlte/plugins/datatables-buttons/js/buttons.print.min.js",
        base_url() . "assets/adlte/plugins/bs-custom-file-input/bs-custom-file-input.min.js",
        base_url() . "assets/js/jquery.mask.min.js?7=" . rand(),
     

            base_url() . "assets/js/acuerdosuscritos.js?7=" . rand(),

        );

      
        if ($_SESSION['perfil']=="1"){
        $data['datos'] = $this->AcuerdosIngreso->get_informeiniciado();
        }
        else
        {
        $data['datos'] = $this->AcuerdosIngreso->get_informeiniciadouser();
        }

        $data['titulo'] = "Acuerdos ";
        $datosareasdeta = $this->IngresoAcu->get_areasedeta();
        $datosarease = $this->IngresoAcu->get_arease();
        $secretarias = $this->IngresoAcu->get_secretarias();
        $programa = $this->ConsultaPro->get_programa();
        $data['programa'] = $programa;
        $data['datosareasdeta'] = $datosareasdeta;
        $data['datosarease'] = $datosarease;
        $data['secretarias'] = $secretarias;

        $this->load->view('plantilla/header', $data);
        $this->load->view('acuerdos/informeiniciados', $data);
        $this->load->view('consultaP/modalesform', $data);
        $this->load->view('plantilla/footer');
    }

    public function verdatellesuscrito ()
    {
        $consecutivo=$this->input->post('consecutivo');
        $datosareasdeta = $this->IngresoAcu->get_areasedeta();
        $datosarease = $this->IngresoAcu->get_arease();
        $secretarias = $this->IngresoAcu->get_secretarias();
        $programa = $this->ConsultaPro->get_programa();
        $data['programa'] = $programa;
        $data['datosareasdeta'] = $datosareasdeta;
        $data['datosarease'] = $datosarease;
        $data['secretarias'] = $secretarias;
        $data['acuerdo'] = $this->AcuerdosIngreso->get_acuerdoingresado($consecutivo);
        $this->load->view('acuerdos/detallesuscrito', $data);
        
        //var_dump ($data);
    }



    
    public function salvaeditadata()
    {
        $consecutivo=$this->input->post('consecutivo');
      
       $dataproyecto=$_POST;
      //  var_dump ($dataproyecto);
        $dataproyecto['usuario_upd']=$_SESSION['usuario'];
    //    $dataproyecto['fecha_creacion']=date('Y-m-d');
    //    $dataproyecto['hora']=date('h:i:sa');
        $dataproyecto['aportegob'] = str_replace(".", "", $dataproyecto['aportegob']);
        $dataproyecto['aportemun'] = str_replace(".", "", $dataproyecto['aportemun']);
        $dataproyecto['aporteotr'] = str_replace(".", "", $dataproyecto['aporteotr']);

        unset($dataproyecto['numerogobernacion']);
        unset($dataproyecto['numeromunicipio']);
        unset($dataproyecto['numerootros']);
        unset($dataproyecto['fechahhora']);

        unset($dataproyecto['consecutivo']);
       
       


        $this->db->trans_begin();

        $original=$this->AcuerdosIngreso->get_original($consecutivo);
        $original['fecha_creacion']=date('Y-m-d');
        $original['hora']=date('h:i:sa');
        $original['usuario_upd']=$_SESSION['usuario'];

        $this->AcuerdosIngreso->ins_logs($original);

             if ($this->db->trans_status() === false) {
                 $this->db->trans_rollback();
                 $respuesta['estado'] = "false";
                 $respuesta['mensaje'] = "Hubo un error generando logs, contacte al administrador ";

                 echo json_encode($respuesta);
            } 
             else
             {
                $this->AcuerdosIngreso->upda_acuerdosseg($consecutivo,$dataproyecto);
                if ($this->db->trans_status() === false) {
                    $this->db->trans_rollback();
                    $respuesta['estado'] = "false";
                    $respuesta['mensaje'] = "Hubo un error actualizando el acuerdo, contacte al administrador ";
   
                    echo json_encode($respuesta);
               } 
               else
               {
                $this->db->trans_commit();
                $respuesta = array();
                $respuesta['estado'] = "true";
                $respuesta['mensaje'] = "Se ha generado actualizado el acuerdo número " . $consecutivo;
                $respuesta['numero'] = $consecutivo;
                echo json_encode($respuesta);
                
               }
               
             


            }




    }



}