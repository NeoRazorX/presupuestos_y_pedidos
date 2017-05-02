<?php

/*
 * This file is part of pedidos_y_pedidos
 * Copyright (C) 2015-2017    Carlos Garcia Gomez  neorazorx@gmail.com
 * Copyright (C) 2017         Itaca Software Libre contacta@itacaswl.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require_once 'plugins/facturacion_base/extras/fs_pdf.php';
require_once 'plugins/facturacion_base/extras/xlsxwriter.class.php';

require_model('almacen.php');
require_model('cliente.php');
require_model('divisa.php');
require_model('forma_pago.php');
require_model('pedido_cliente.php');
require_model('pedido_proveedor.php');
require_model('proveedor.php');
require_model('serie.php');

class informe_pedidos extends fs_controller
{

   public $agente;
   public $almacen;
   public $codagente;
   public $codalmacen;
   public $coddivisa;
   public $codpago;
   public $codserie;
   public $desde;
   public $divisa;
   public $estado;
   public $forma_pago;
   public $hasta;
   public $multi_almacen;
   public $pedidos_cli;
   public $pedidos_pro;
   public $serie;
   private $where_compras;
   private $where_ventas;
   public $cliente;
   public $proveedor;
   public $tipo;
   public $generar;

   public function __construct()
   {
      parent::__construct(__CLASS__, ucfirst(FS_PEDIDOS), 'informes', FALSE, TRUE);
   }

   protected function private_core()
   {
      /// declaramos los objetos sólo para asegurarnos de que existen las tablas
      $this->pedido_cli = new pedido_cliente();
      $this->pedido_pro = new pedido_proveedor();

      $this->agente = new agente();
      $this->almacen = new almacen();
      $this->divisa = new divisa();
      $this->forma_pago = new forma_pago();
      $this->serie = new serie();
      $this->cliente = FALSE;
      $this->proveedor = FALSE;
      $this->tipo = FALSE;
      $this->generar = FALSE;

      $fsvar = new fs_var();
      $this->multi_almacen = $fsvar->simple_get('multi_almacen');

      $this->desde = Date('01-m-Y', strtotime('-14 months'));
      if(isset($_REQUEST['desde']))
      {
         $this->desde = $_REQUEST['desde'];
      }

      $this->hasta = Date('t-m-Y');
      if(isset($_REQUEST['hasta']))
      {
         $this->hasta = $_REQUEST['hasta'];
      }

      $this->codserie = FALSE;
      if(isset($_REQUEST['codserie']))
      {
         $this->codserie = $_REQUEST['codserie'];
      }

      $this->codpago = FALSE;
      if(isset($_REQUEST['codpago']))
      {
         $this->codpago = $_REQUEST['codpago'];
      }

      $this->codagente = FALSE;
      if(isset($_REQUEST['codagente']))
      {
         $this->codagente = $_REQUEST['codagente'];
      }

      $this->codalmacen = FALSE;
      if(isset($_REQUEST['codalmacen']))
      {
         $this->codalmacen = $_REQUEST['codalmacen'];
      }

      $this->coddivisa = $this->empresa->coddivisa;
      if(isset($_REQUEST['coddivisa']))
      {
         if($_REQUEST['coddivisa'] == 'all')
         {
            $this->coddivisa = FALSE;
         }
         else
            $this->coddivisa = $_REQUEST['coddivisa'];
      }

      $this->estado = '';
      if(isset($_REQUEST['estado']))
      {
         $this->estado = $_REQUEST['estado'];
      }
      
      if( isset($_REQUEST['buscar_cliente']) )
      {
         $this->buscar_cliente();
      }
      else if (isset($_REQUEST['buscar_proveedor']))
      {
         $this->buscar_proveedor();
      }
     
      if( isset($_REQUEST['codcliente']) )
      {
         if($_REQUEST['codcliente'] != '')
         {
            $cli0 = new cliente();
            $this->cliente = $cli0->get($_REQUEST['codcliente']);
         }
      }
      
      if( isset($_REQUEST['codproveedor']) )
      {
         if($_REQUEST['codproveedor'] != '')
         {
            $prov0 = new proveedor();
            $this->proveedor = $prov0->get($_REQUEST['codproveedor']);
         }
      }

      $this->set_where();
      
      /// ¿¿¿listados???
      if( isset($_POST['generar']) )
      {
         if($_POST['generar'] == 'pdfcli')
         {
            $this->tipo = 'ventas';
            $this->pdf_pedidos();
         }
         else if($_POST['generar'] == 'xlscli')
         {
            $this->tipo = 'ventas';
            $this->xls_pedidos();
         }
         else if ($_POST['generar'] == 'csvcli')
         {
            $this->tipo = 'ventas';
            $this->csv_pedidos();
         }
         else if ($_POST['generar'] == 'pdfprov')
         {
            $this->tipo = 'compras';
            $this->pdf_pedidos();
         }
         else if ($_POST['generar'] == 'xlsprov')
         {
            $this->tipo = 'compras';
            $this->xls_pedidos();
         }
         else if ($_POST['generar'] == 'csvprov')
         {
            $this->tipo = 'compras';
            $this->csv_pedidos();
         }
      }
   }

   private function set_where()
   {
      $this->where_compras = " WHERE fecha >= " . $this->empresa->var2str($this->desde)
              . " AND fecha <= " . $this->empresa->var2str($this->hasta);

      if($this->codserie)
      {
         $this->where_compras .= " AND codserie = " . $this->empresa->var2str($this->codserie);
      }

      if($this->codagente)
      {
         $this->where_compras .= " AND codagente = " . $this->empresa->var2str($this->codagente);
      }

      if($this->codalmacen)
      {
         $this->where_compras .= " AND codalmacen = " . $this->empresa->var2str($this->codalmacen);
      }

      if($this->coddivisa)
      {
         $this->where_compras .= " AND coddivisa = " . $this->empresa->var2str($this->coddivisa);
      }

      if($this->codpago)
      {
         $this->where_compras .= " AND codpago = " . $this->empresa->var2str($this->codpago);
      }

      $this->where_ventas = $this->where_compras;
      if($this->estado != '')
      {
         switch ($this->estado)
         {
            case '0':
               $this->where_compras .= " AND idalbaran IS NULL";
               $this->where_ventas .= " AND idalbaran IS NULL AND status = '0'";
               break;

            case '1':
               $this->where_compras .= " AND idalbaran IS NOT NULL";
               $this->where_ventas .= " AND status = '1'";
               break;

            case '2':
               $this->where_compras .= " AND 1 = 2";
               $this->where_ventas .= " AND status = '2'";
               break;
         }
      }
   }

   public function stats_months()
   {
      $stats = array();
      $stats_cli = $this->stats_months_aux('pedidoscli');
      $stats_pro = $this->stats_months_aux('pedidosprov');
      $meses = array(
          1 => 'ene',
          2 => 'feb',
          3 => 'mar',
          4 => 'abr',
          5 => 'may',
          6 => 'jun',
          7 => 'jul',
          8 => 'ago',
          9 => 'sep',
          10 => 'oct',
          11 => 'nov',
          12 => 'dic'
      );

      foreach($stats_cli as $i => $value)
      {
         $mesletra = "";
         $ano = "";

         if(!empty($value['month']))
         {
            $mesletra = $meses[intval(substr((string) $value['month'], 0, strlen((string) $value['month']) - 2))];
            $ano = substr((string) $value['month'], -2);
         }

         $stats[$i] = array(
             'month' => $mesletra . $ano,
             'total_cli' => round($value['total'], FS_NF0),
             'total_pro' => 0
         );
      }

      foreach($stats_pro as $i => $value)
      {
         $stats[$i]['total_pro'] = round($value['total'], FS_NF0);
      }

      return $stats;
   }

   private function stats_months_aux($table_name = 'pedidoscli')
   {
      $stats = array();

      /// inicializamos los resultados
      foreach($this->date_range($this->desde, $this->hasta, '+1 month', 'my') as $date)
      {
         $i = intval($date);
         $stats[$i] = array('month' => $i, 'total' => 0);
      }

      if(strtolower(FS_DB_TYPE) == 'postgresql')
      {
         $sql_aux = "to_char(fecha,'FMMMYY')";
      }
      else
      {
         $sql_aux = "DATE_FORMAT(fecha, '%m%y')";
      }

      $sql = "SELECT " . $sql_aux . " as mes, SUM(neto) as total FROM " . $table_name;
      if($table_name == 'pedidoscli')
      {
         $sql .= $this->where_ventas;
      }
      else
      {
         $sql .= $this->where_compras;
      }
      $sql .= " GROUP BY " . $sql_aux . " ORDER BY mes ASC;";

      $data = $this->db->select($sql);
      if($data)
      {
         foreach($data as $d)
         {
            $i = intval($d['mes']);
            $stats[$i]['total'] = floatval($d['total']);
         }
      }

      return $stats;
   }

   public function stats_years()
   {
      $stats = array();
      $stats_cli = $this->stats_years_aux('pedidoscli');
      $stats_pro = $this->stats_years_aux('pedidosprov');

      foreach($stats_cli as $i => $value)
      {
         $stats[$i] = array(
             'year' => $value['year'],
             'total_cli' => round($value['total'], FS_NF0),
             'total_pro' => 0
         );
      }

      foreach($stats_pro as $i => $value)
      {
         $stats[$i]['total_pro'] = round($value['total'], FS_NF0);
      }

      return $stats;
   }

   private function stats_years_aux($table_name = 'pedidoscli', $num = 4)
   {
      $stats = array();

      /// inicializamos los resultados
      foreach($this->date_range($this->desde, $this->hasta, '+1 year', 'Y') as $date)
      {
         $i = intval($date);
         $stats[$i] = array('year' => $i, 'total' => 0);
      }

      if(strtolower(FS_DB_TYPE) == 'postgresql')
      {
         $sql_aux = "to_char(fecha,'FMYYYY')";
      }
      else
         $sql_aux = "DATE_FORMAT(fecha, '%Y')";

      $sql = "SELECT " . $sql_aux . " as ano, sum(neto) as total FROM " . $table_name;
      if($table_name == 'pedidoscli')
      {
         $sql .= $this->where_ventas;
      }
      else
      {
         $sql .= $this->where_compras;
      }
      $sql .= " GROUP BY " . $sql_aux . " ORDER BY ano ASC;";

      $data = $this->db->select($sql);
      if($data)
      {
         foreach($data as $d)
         {
            $i = intval($d['ano']);
            $stats[$i]['total'] = floatval($d['total']);
         }
      }

      return $stats;
   }

   private function date_range($first, $last, $step = '+1 day', $format = 'd-m-Y')
   {
      $dates = array();
      $current = strtotime($first);
      $last = strtotime($last);

      while($current <= $last)
      {
         $dates[] = date($format, $current);
         $current = strtotime($step, $current);
      }

      return $dates;
   }

   public function stats_series($tabla = 'pedidosprov')
   {
      $stats = array();

      $sql = "select codserie,sum(neto) as total from " . $tabla;
      if($tabla == 'pedidoscli')
      {
         $sql .= $this->where_ventas;
      }
      else
      {
         $sql .= $this->where_compras;
      }
      $sql .= " group by codserie order by total desc;";

      $data = $this->db->select($sql);
      if($data)
      {
         foreach($data as $d)
         {
            $serie = $this->serie->get($d['codserie']);
            if($serie)
            {
               $stats[] = array(
                   'txt' => $serie->descripcion,
                   'total' => round(floatval($d['total']), FS_NF0)
               );
            }
            else
            {
               $stats[] = array(
                   'txt' => $d['codserie'],
                   'total' => round(floatval($d['total']), FS_NF0)
               );
            }
         }
      }

      return $stats;
   }

   public function stats_agentes($tabla = 'pedidosprov')
   {
      $stats = array();

      $sql = "select codagente,sum(neto) as total from " . $tabla;
      if($tabla == 'pedidoscli')
      {
         $sql .= $this->where_ventas;
      }
      else
      {
         $sql .= $this->where_compras;
      }
      $sql .= " group by codagente order by total desc;";

      $data = $this->db->select($sql);
      if($data)
      {
         foreach($data as $d)
         {
            if(is_null($d['codagente']))
            {
               $stats[] = array(
                   'txt' => 'Ninguno',
                   'total' => round(floatval($d['total']), FS_NF0)
               );
            }
            else
            {
               $agente = $this->agente->get($d['codagente']);
               if($agente)
               {
                  $stats[] = array(
                      'txt' => $agente->get_fullname(),
                      'total' => round(floatval($d['total']), FS_NF0)
                  );
               }
               else
               {
                  $stats[] = array(
                      'txt' => $d['codagente'],
                      'total' => round(floatval($d['total']), FS_NF0)
                  );
               }
            }
         }
      }

      return $stats;
   }

   public function stats_almacenes($tabla = 'pedidosprov')
   {
      $stats = array();

      $sql = "select codalmacen,sum(neto) as total from " . $tabla;
      if($tabla == 'pedidoscli')
      {
         $sql .= $this->where_ventas;
      }
      else
      {
         $sql .= $this->where_compras;
      }
      $sql .= " group by codalmacen order by total desc;";

      $data = $this->db->select($sql);
      if($data)
      {
         foreach($data as $d)
         {
            $alma = $this->almacen->get($d['codalmacen']);
            if($alma)
            {
               $stats[] = array(
                   'txt' => $alma->nombre,
                   'total' => round(floatval($d['total']), FS_NF0)
               );
            }
            else
            {
               $stats[] = array(
                   'txt' => $d['codalmacen'],
                   'total' => round(floatval($d['total']), FS_NF0)
               );
            }
         }
      }

      return $stats;
   }

   public function stats_formas_pago($tabla = 'pedidosprov')
   {
      $stats = array();

      $sql = "select codpago,sum(neto) as total from " . $tabla;
      if($tabla == 'pedidoscli')
      {
         $sql .= $this->where_ventas;
      }
      else
      {
         $sql .= $this->where_compras;
      }
      $sql .= " group by codpago order by total desc;";

      $data = $this->db->select($sql);
      if($data)
      {
         foreach($data as $d)
         {
            $formap = $this->forma_pago->get($d['codpago']);
            if($formap)
            {
               $stats[] = array(
                   'txt' => $formap->descripcion,
                   'total' => round(floatval($d['total']), FS_NF0)
               );
            }
            else
            {
               $stats[] = array(
                   'txt' => $d['codpago'],
                   'total' => round(floatval($d['total']), FS_NF0)
               );
            }
         }
      }

      return $stats;
   }

   public function stats_estados($tabla = 'pedidosprov')
   {
      $stats = array();

      if($tabla == 'pedidoscli')
      {
         $stats = $this->stats_estados_pedidoscli();
      }
      else
      {
         /// aprobados
         $sql = "select sum(neto) as total from " . $tabla;
         $sql .= $this->where_compras;
         $sql .= " and idalbaran is not null order by total desc;";

         $data = $this->db->select($sql);
         if($data)
         {
            if(floatval($data[0]['total']))
            {
               $stats[] = array(
                   'txt' => 'aprobado',
                   'total' => round(floatval($data[0]['total']), FS_NF0)
               );
            }
         }

         /// pendientes
         $sql = "select sum(neto) as total from " . $tabla;
         $sql .= $this->where_compras;
         $sql .= " and idalbaran is null order by total desc;";

         $data = $this->db->select($sql);
         if($data)
         {
            if(floatval($data[0]['total']))
            {
               $stats[] = array(
                   'txt' => 'pendiente',
                   'total' => round(floatval($data[0]['total']), FS_NF0)
               );
            }
         }
      }

      return $stats;
   }

   private function stats_estados_pedidoscli()
   {
      $stats = array();
      $tabla = 'pedidoscli';

      $sql = "select status,sum(neto) as total from " . $tabla;
      $sql .= $this->where_ventas;
      $sql .= " group by status order by total desc;";

      $data = $this->db->select($sql);
      if($data)
      {
         $estados = array(
             0 => 'pendiente',
             1 => 'aprobado',
             2 => 'rechazado',
             3 => 'validado parcialmente'
         );

         foreach($data as $d)
         {
            $stats[] = array(
                'txt' => $estados[$d['status']],
                'total' => round(floatval($d['total']), FS_NF0)
            );
         }
      }

      return $stats;
   }

   /**
    * Esta función sirve para generar el javascript necesario para que la vista genere
    * las gráficas, ahorrando mucho código.
    * @param type $data
    * @param type $chart_id
    * @return string
    */
   public function generar_chart_pie_js(&$data, $chart_id)
   {
      $js_txt = '';

      if($data)
      {
         echo "var " . $chart_id . "_labels = [];\n";
         echo "var " . $chart_id . "_data = [];\n";

         foreach($data as $d)
         {
            echo $chart_id . '_labels.push("' . $d['txt'] . '");' . "\n";
            echo $chart_id . '_data.push("' . $d['total'] . '");' . "\n";
         }

         /// hacemos el apaño para evitar el problema de charts.js con tabs en boostrap
         echo "var " . $chart_id . "_ctx = document.getElementById('" . $chart_id . "').getContext('2d');\n";
         echo $chart_id . "_ctx.canvas.height = 100;\n";

         echo "var " . $chart_id . "_chart = new Chart(" . $chart_id . "_ctx, {
            type: 'pie',
            data: {
               labels: " . $chart_id . "_labels,
               datasets: [
                  {
                     backgroundColor: default_colors,
                     data: " . $chart_id . "_data
                  }
               ]
            }
         });";
      }

      return $js_txt;
   }
   
   private function buscar_cliente()
   {
      /// desactivamos la plantilla HTML
      $this->template = FALSE;
      
      $cli = new cliente();
      $json = array();
      foreach($cli->search($_REQUEST['buscar_cliente']) as $cli)
      {
         $json[] = array('value' => $cli->nombre, 'data' => $cli->codcliente);
      }
      
      header('Content-Type: application/json');
      echo json_encode( array('query' => $_REQUEST['buscar_cliente'], 'suggestions' => $json) );
   }
   
   private function buscar_proveedor()
   {
      /// desactivamos la plantilla HTML
      $this->template = FALSE;
      
      $prov = new proveedor();
      $json = array();
      foreach($prov->search($_REQUEST['buscar_proveedor']) as $prov)
      {
         $json[] = array('value' => $prov->nombre, 'data' => $prov->codproveedor);
      }
      
      header('Content-Type: application/json');
      echo json_encode( array('query' => $_REQUEST['buscar_proveedor'], 'suggestions' => $json) );
   }
   
   public function resultados()
   {
      $codigo = false;
      
      if($this->tipo)
      {
         if($this->tipo == 'ventas')
         {
            $ped0 = new pedido_cliente();
            if($this->cliente)
            {
               $codigo = $this->cliente->codcliente;
            }
         }
         else
         {
            $ped0 = new pedido_proveedor();
            if($this->proveedor)
            {
               $codigo = $this->proveedor->codproveedor;
            }
         }
      }
      
      $pedidos = $ped0->all_desde(
              $this->desde,
              $this->hasta,
              $this->codserie,
              $this->codagente,
              $codigo,
              $this->estado,
              $this->codpago,
              $this->codalmacen,
              $this->coddivisa
      );
      
      return $pedidos;
   }
   
   
   private function pdf_pedidos()
   {
      /// desactivamos el motor de plantillas
      $this->template = FALSE;
      
      $pdf_doc = new fs_pdf('a4', 'landscape', 'Courier');
      $pdf_doc->pdf->addInfo('Title', FS_PEDIDOS . ' del ' . $this->desde . ' al ' . $this->hasta);
      $pdf_doc->pdf->addInfo('Subject', FS_PEDIDOS . ' del ' . $this->desde . ' al ' . $this->hasta);
      $pdf_doc->pdf->addInfo('Author', $this->empresa->nombre);
      
      $pedidos = $this->resultados();
      if($pedidos)
      {
         $total_lineas = count($pedidos);
         $linea_actual = 0;
         $lppag = 61;
         $pagina = 1;
         $totalbase = $total = 0;

         while($linea_actual < $total_lineas)
         {
            if($linea_actual > 0)
            {
               $pdf_doc->pdf->ezNewPage();
               $pagina++;
            }

            /// encabezado
            $pdf_doc->pdf->ezText( $this->fix_html($this->empresa->nombre). " - ".FS_PEDIDOS." - de ".$this->tipo." del ".$this->desde." al ".$this->hasta );
            
            if($this->codserie)
            {
               $pdf_doc->pdf->ezText( strtoupper(FS_SERIE).': ' . $this->codserie );
               $lppag--;
            }

            if($this->codagente)
            {
               $age0 = new agente();
               $agente = $age0->get($this->codagente);
               if($agente)
               {
                  $pdf_doc->pdf->ezText("Empleado: " . $this->fix_html($agente->nombre));
                  $lppag--;
               }
            }
            if($this->tipo)
            {
               if($this->tipo == 'ventas') {
                  if($this->cliente)
                  {
                     $pdf_doc->pdf->ezText("Cliente: " . $this->fix_html($this->cliente->nombre));
                     $lppag--;
                  }
               }
               else
               {
                  if($this->proveedor)
                  {
                     $pdf_doc->pdf->ezText("Proveedor: " . $this->fix_html($this->proveedor->nombre));
                     $lppag--;
                  }
               }
            }

            if($this->estado != '')
            {
               switch ($this->estado)
               {
                  case '0':
                     $pdf_doc->pdf->ezText("Estado: Pendientes");
                     break;

                  case '1':
                     $pdf_doc->pdf->ezText("Estado: Aprobados");
                     break;

                  case '2':
                     $pdf_doc->pdf->ezText("Estado: Pendientes");
                     break;
               }
            }

            if($this->codpago)
            {
               $fp0 = new forma_pago();
               $forma_pago = $fp0->get($this->codpago);
               if($forma_pago)
               {
                  $pdf_doc->pdf->ezText("Forma de pago: " . $this->fix_html($forma_pago->descripcion));
                  $lppag--;
               }
            }
            
            if($this->almacen)
            {
               $alm0 = new almacen();
               $almacen = $alm0->get($this->codalmacen);
               if($almacen)
               {
                  $pdf_doc->pdf->ezText("Almacén: " . $this->fix_html($almacen->nombre));
                  $lppag--;
               }
            }

            $pdf_doc->pdf->ezText("\n", 8);
            
            $nombre = 'nombrecliente';
            if($this->tipo == 'compras')
            {
               $nombre = 'nombre';
            }

            /// tabla principal
            $pdf_doc->new_table();
            $pdf_doc->add_table_header(
                    array(
                        'serie' => '<b>' . strtoupper(FS_SERIE) . '</b>',
                        'pedido' => '<b>Ped.</b>',
                        'fecha' => '<b>Fecha</b>',
                        'descripcion' => '<b>Descripción</b>',
                        'cifnif' => '<b>' . FS_CIFNIF . '</b>',
                        'base' => '<b>Base Im.</b>',
                        'total' => '<b>Total</b>'
                    )
            );
            for($i = 0; $i < $lppag AND $linea_actual < $total_lineas; $i++)
            {
               $linea = array(
                   'serie' => $pedidos[$linea_actual]->codserie,
                   'pedido' => $pedidos[$linea_actual]->codigo,
                   'fecha' => $pedidos[$linea_actual]->fecha,
                   'descripcion' => $this->fix_html($pedidos[$linea_actual]->$nombre),
                   'cifnif' => $pedidos[$linea_actual]->cifnif,
                   'base' => $pedidos[$linea_actual]->neto,
                   'total' => $pedidos[$linea_actual]->total,
               );

               $pdf_doc->add_table_row($linea);

               $i++;
               $totalbase += $pedidos[$linea_actual]->neto;
               $total += $pedidos[$linea_actual]->total;
               $linea_actual++;
            }

            $pdf_doc->save_table(
                    array(
                        'fontSize' => 8,
                        'cols' => array(
                            'base' => array('justification' => 'right'),
                            'total' => array('justification' => 'right')
                        ),
                        'shaded' => 0,
                        'width' => 780
                    )
            );


            /// Rellenamos la última tabla
            $pdf_doc->set_y(70);
            $pdf_doc->new_table();
            $titulo = array('pagina' => '<b>Suma y sigue</b>');
            $fila = array('pagina' => $pagina . '/' . ($pagina + ceil(($total_lineas - $linea_actual) / $lppag)));
            $opciones = array(
                'fontSize' => 8,
                'cols' => array('base' => array('justification' => 'right')),
                'showLines' => 1,
                'width' => 780
            );
            
            $titulo['base'] = '<b>Base imponible</b>';
            $titulo['total'] = '<b>Total</b>';
            $fila['base'] = $this->show_precio($totalbase);
            $fila['total'] = $this->show_precio($total);
            $opciones['cols']['base'] = array('justification' => 'right');
            $opciones['cols']['total'] = array('justification' => 'right');
            $pdf_doc->add_table_header($titulo);
            $pdf_doc->add_table_row($fila);
            $pdf_doc->save_table($opciones);
         }
      }
      else
      {
         $pdf_doc->pdf->ezText($this->empresa->nombre . " - " . FS_PEDIDOS . "  de ".$this->tipo." del " . $this->desde . " al " . $this->hasta . ":\n\n", 14);
         $pdf_doc->pdf->ezText("Ninguno.\n\n", 14);
      }

      $pdf_doc->show();
   }

   private function fix_html($txt)
   {
      $newt = str_replace('&lt;', '<', $txt);
      $newt = str_replace('&gt;', '>', $newt);
      $newt = str_replace('&quot;', '"', $newt);
      $newt = str_replace('&#39;', "'", $newt);
      return $newt;
   }
   
   private function xls_pedidos()
   {
      
      $this->template = FALSE;
      header("Content-Disposition: attachment; filename=\"informe_pedidos_".time().".xlsx\"");
      header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
      header('Content-Transfer-Encoding: binary');
      header('Cache-Control: must-revalidate');
      header('Pragma: public');
      
       $header = array(
         'almacen' => 'string',
         'codigo' => 'string',
         'serie' => 'string',
         FS_NUMERO2 => 'string',
         'fecha' => 'string',
         'descripcion' => 'string',
         FS_CIFNIF => 'string',
         'base' => '#,##0.00',
         'total' => '#,##0.00;[RED]-#,##0.00',
      );

      $data = array();

      
      $pedidos = $this->resultados();

      $nombre = 'nombrecliente';
      if($this->tipo == 'compras')
      {
         $nombre = 'nombre';
      }

      if($pedidos)
      {
         foreach($pedidos as $ped)
         {
            $linea = array(
                'almacen' => $ped->codalmacen,
                'codigo' => $ped->codigo,
                'serie' => $ped->codserie,
                FS_NUMERO2 => $ped->numero2,
                'fecha' => $ped->fecha,
                'descripcion' => $ped->$nombre,
                FS_CIFNIF => $ped->cifnif,
                'base' => $ped->neto,
                'total' => $ped->total,
            );

            $data[] = $linea;
         }
      }
      
      $writter = new XLSXWriter();
      $writter->setAuthor('Generador Excel FS');
      $writter->writeSheetHeader('Pedidos', $header);
      foreach($data as $row)
      {
         $writter->writeSheetRow('Pedidos', $row);
      }
      
      $writter->writeToStdOut();
   }
   
   private function csv_pedidos()
   {
      $this->template = FALSE;
      header("content-type:application/csv;charset=UTF-8");
      header("Content-Disposition: attachment; filename=\"informe_pedidos.csv\"");
      echo "almacen,serie," . FS_NUMERO2 . ",pedido,fecha,descripcion," . FS_CIFNIF
      . ",base,total\n";

      $pedidos = $this->resultados();

      $nombre = 'nombrecliente';
      if($this->tipo == 'compras')
      {
         $nombre = 'nombre';
      }

      if($pedidos)
      {
         foreach($pedidos as $ped)
         {
            $linea = array(
                'almacen' => $ped->codalmacen,
                'serie' => $ped->codserie,
                'numero2' => $ped->numero2,
                'pedido' => $ped->numero,
                'fecha' => $ped->fecha,
                'descripcion' => $ped->$nombre,
                'cifnif' => $ped->cifnif,
                'base' => $ped->neto,
                'total' => $ped->total
            );

            echo '"' . join('","', $linea) . "\"\n";
         }
      }
   }

}
