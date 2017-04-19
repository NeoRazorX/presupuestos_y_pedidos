<?php

/*
 * This file is part of presupuestos_y_presupuestos
 * Copyright (C) 2015-2017    Carlos Garcia Gomez  neorazorx@gmail.com
 * Copyright (C) 2017         Itaca Software Libre contacta@itacaswl.com
 * Copyright (C) 2017         PC REDNET ( Luis Miguel Pérez romero ) luismi@pcrednet.com
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
require_model('presupuesto_cliente.php');
require_model('serie.php');

class informe_presupuestos extends fs_controller
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
   public $forma_pago;
   public $hasta;
   public $multi_almacen;
   public $presupuestos_cli;
   public $serie;
   private $where;
   public $estado;
   public $cliente;

   public function __construct()
   {
      parent::__construct(__CLASS__, ucfirst(FS_PRESUPUESTOS), 'informes', FALSE, TRUE);
   }

   protected function private_core()
   {
      /// declaramos los objetos sólo para asegurarnos de que existen las tablas
      $this->presupuesto_cli = new presupuesto_cliente();

      $this->agente = new agente();
      $this->almacen = new almacen();
      $this->divisa = new divisa();
      $this->forma_pago = new forma_pago();
      $this->serie = new serie();
      $this->cliente = FALSE;

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
         $this->coddivisa = $_REQUEST['coddivisa'];
      }

      $this->estado = "";
      if(isset($_REQUEST['estado']))
      {
         $this->estado = $_REQUEST['estado'];
      }

      if(isset($_REQUEST['buscar_cliente']))
      {
         $this->buscar_cliente();
      }

      else if(isset($_REQUEST['codcliente']))
      {
         if($_REQUEST['codcliente'] != '')
         {
            $cli0 = new cliente();
            $this->cliente = $cli0->get($_REQUEST['codcliente']);
         }
      }

      //listados???
      if(isset($_POST['pdf']))
      {
         if($_POST['pdf'] == 'TRUE')
         {
            $this->pdf_presupuestos_cli();
         }
      }

      $this->set_where();
   }

   private function set_where()
   {
      $this->where = " WHERE fecha >= " . $this->empresa->var2str($this->desde)
              . " AND fecha <= " . $this->empresa->var2str($this->hasta);

      if($this->codserie)
      {
         $this->where .= " AND codserie = " . $this->empresa->var2str($this->codserie);
      }

      if($this->codagente)
      {
         $this->where .= " AND codagente = " . $this->empresa->var2str($this->codagente);
      }

      if($this->codalmacen)
      {
         $this->where .= " AND codalmacen = " . $this->empresa->var2str($this->codalmacen);
      }

      if($this->coddivisa)
      {
         $this->where .= " AND coddivisa = " . $this->empresa->var2str($this->coddivisa);
      }

      if($this->estado)
      {
         if($this->estado =='3')
         {
            $this->where .= " AND idpedido is NULL AND status = 0";
         }
         else if ($this->estado=='1')
         {
            $this->where .= " AND status = '1'";
         }
         else if ($this->estado=='2')
         {
            $this->where .= " AND status = '2'";
         }
      }

      if($this->cliente)
      {
         $this->where .= " AND codcliente = " . $this->empresa->var2str($this->cliente->codcliente);
      }
   }

   public function stats_months()
   {
      $stats = array();
      $stats_cli = $this->stats_months_aux();
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
         );
      }


      return $stats;
   }

   private function stats_months_aux()
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

      $sql = "SELECT " . $sql_aux . " as mes, SUM(neto) as total FROM presupuestoscli"
              . $this->where . " GROUP BY " . $sql_aux . " ORDER BY mes ASC;";

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
      $stats_cli = $this->stats_years_aux();

      foreach($stats_cli as $i => $value)
      {
         $stats[$i] = array(
             'year' => $value['year'],
             'total_cli' => round($value['total'], FS_NF0),
         );
      }

      return $stats;
   }

   private function stats_years_aux($num = 4)
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

      $data = $this->db->select("SELECT " . $sql_aux . " as ano, sum(neto) as total FROM presupuestoscli"
              . $this->where . " GROUP BY " . $sql_aux . " ORDER BY ano ASC;");

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

   public function stats_series()
   {
      $stats = array();

      $sql = "select codserie,sum(neto) as total from presupuestoscli";
      $sql .= $this->where;
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

   public function stats_agentes()
   {
      $stats = array();

      $sql = "select codagente,sum(neto) as total from presupuestoscli";
      $sql .= $this->where;
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

   public function stats_almacenes()
   {
      $stats = array();

      $sql = "select codalmacen,sum(neto) as total from presupuestoscli";
      $sql .= $this->where;
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

   public function stats_formas_pago()
   {
      $stats = array();

      $sql = "select codpago,sum(neto) as total from presupuestoscli";
      $sql .= $this->where;
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

   public function stats_estados()
   {

      $stats = $this->stats_estados_presupuestoscli();

      return $stats;
   }

   private function stats_estados_presupuestoscli()
   {
      $stats = array();


      $sql = "select status,sum(neto) as total from presupuestoscli ";
      $sql .= $this->where;
      $sql .= " group by status order by total desc;";

      $data = $this->db->select($sql);
      if($data)
      {
         $estados = array(
             0 => 'Pendientes',
             1 => 'Aprovados',
             2 => 'Rechazados',
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

   public function stats_divisas()
   {
      $stats = array();

      $sql = "select coddivisa,sum(neto) as total from presupuestoscli";
      $sql .= $this->where;
      $sql .= " group by coddivisa order by total desc;";

      $data = $this->db->select($sql);
      if($data)
      {
         foreach($data as $d)
         {
            $divisa = $this->divisa->get($d['coddivisa']);
            if($divisa)
            {
               $stats[] = array(
                   'txt' => $divisa->descripcion,
                   'total' => round(floatval($d['total']), FS_NF0)
               );
            }
            else
            {
               $stats[] = array(
                   'txt' => $d['coddivisa'],
                   'total' => round(floatval($d['total']), FS_NF0)
               );
            }
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
            echo $chart_id . '_labels.push("' . $d['txt'] . '"); ';
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

   /**
    * 
    * @return type array datos
    */
   public function stats_clientes()
   {
      $stats = array();

      $sql = "select codcliente,sum(neto) as total from presupuestoscli";
      $sql .= $this->where;
      $sql .= " group by codcliente order by total desc;";

      $data = $this->db->select($sql);
      if($data)
      {
         foreach($data as $d)
         {
            if(is_null($d['codcliente']))
            {
               $stats[] = array(
                   'txt' => 'Ninguno',
                   'total' => round(floatval($d['total']), FS_NF0)
               );
            }
            else
            {
               $cli0 = new cliente();
               $cliente = $cli0->get($d['codcliente']);
               if($cliente)
               {
                  $stats[] = array(
                      'txt' => $cliente->nombre,
                      'total' => round(floatval($d['total']), FS_NF0)
                  );
               }
               else
               {
                  $stats[] = array(
                      'txt' => $d['codcliente'],
                      'total' => round(floatval($d['total']), FS_NF0)
                  );
               }
            }
         }
      }

      return $stats;
   }

   private function pdf_presupuestos_cli()
   {
      /// desactivamos el motor de plantillas
      

      $pdf_doc = new fs_pdf('a4', 'landscape', 'Courier');
      $pdf_doc->pdf->addInfo('Title', FS_PRESUPUESTOS . ' del ' . $this->desde . ' al ' . $this->hasta);
      $pdf_doc->pdf->addInfo('Subject', FS_PRESUPUESTOS . ' del ' . $this->desde . ' al ' . $this->hasta);
      $pdf_doc->pdf->addInfo('Author', $this->empresa->nombre);

      $codserie = FALSE;
      if($this->codserie)
      {
         $codserie = $this->codserie;
      }

      $codagente = FALSE;
      if($this->codagente)
      {
         $codagente = $this->codagente;
      }

      $codcliente = FALSE;
      if($this->cliente)
      {
         $codcliente = $this->cliente->codcliente;
      }

      $estado = FALSE;
      if($this->estado)
      {
         $estado = $this->estado;
      }

      $forma_pago = FALSE;
      if($this->codpago)
      {
         $forma_pago = $this->codpago;
      }

      $divisa = FALSE;
      if($this->coddivisa)
      {
         $divisa = $this->coddivisa;
      }

      $almacen = FALSE;
      if($this->codalmacen)
      {
         $almacen = $this->codalmacen;
      }

      $pre0 = new presupuesto_cliente();
      $presupuestos = $pre0->all_desde($this->desde, $this->hasta, $codserie, $codagente, $codcliente, $estado, $forma_pago, $almacen, $divisa);
      if($presupuestos)
      {
         $total_lineas = count($presupuestos);
         $linea_actual = 0;
         $lppag = 61;
         $pagina = 1;

         while($linea_actual < $total_lineas)
         {
            if($linea_actual > 0)
            {
               $pdf_doc->pdf->ezNewPage();
               $pagina++;
            }

            /// encabezado
            $pdf_doc->pdf->ezText( $this->fix_html($this->empresa->nombre). " - ".FS_PRESUPUESTOS." - de venta del ".$this->desde." al ".$this->hasta );
            
            if($codserie)
            {
               $pdf_doc->pdf->ezText("Serie: " . $codserie);
               $lppag--;
            }

            if($codagente)
            {
               $age0 = new agente();
               $agente = $age0->get($this->codagente);
               $pdf_doc->pdf->ezText("Agente: " . $this->fix_html($agente->nombre));
               $lppag--;
            }

            if($codcliente)
            {
               $cli0 = new cliente();
               $cliente = $cli0->get($this->cliente->codcliente);
               $pdf_doc->pdf->ezText("Cliente: " . $this->fix_html($cliente->nombre));
               $lppag--;
            }

            if($estado)
            {
               $lppag--;
               if($estado == '3')
               {
                  $pdf_doc->pdf->ezText("Estado: Pendientes");
               }
               else if ($estado == '1')
               {
                  $pdf_doc->pdf->ezText("Estado: Aprobados");
               }
               else if ($estado == '2')
               {
                  $pdf_doc->pdf->ezText("Estado: Rechazados");
               }
            }

            if($forma_pago)
            {
               $fp0 = new forma_pago();
               $forma_pago = $fp0->get($this->codpago);
               $pdf_doc->pdf->ezText("Forma de pago: " . $this->fix_html($forma_pago->descripcion));
               $lppag--;
            }
            
            if($almacen)
            {
               $alm0 = new almacen();
               $almacen = $alm0->get($this->codalmacen);
               $pdf_doc->pdf->ezText("Almacén: " . $this->fix_html($almacen->nombre));
               $lppag--;
            }

            $pdf_doc->pdf->ezText("\n", 8);


            /// tabla principal
            $pdf_doc->new_table();
            $pdf_doc->add_table_header(
                    array(
                        'serie' => '<b>Serie</b>',
                        'presupuesto' => '<b>Pres.</b>',
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
                   'serie' => $presupuestos[$linea_actual]->codserie,
                   'presupuesto' => $presupuestos[$linea_actual]->codigo,
                   'fecha' => $presupuestos[$linea_actual]->fecha,
                   'descripcion' => $this->fix_html($presupuestos[$linea_actual]->nombrecliente),
                   'cifnif' => $presupuestos[$linea_actual]->cifnif,
                   'base' => $presupuestos[$linea_actual]->neto,
                   'total' => $presupuestos[$linea_actual]->total,
               );

               $pdf_doc->add_table_row($linea);


               $i++;
               $totalbase += $presupuestos[$linea_actual]->neto;
               $total += $presupuestos[$linea_actual]->total;
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
         $pdf_doc->pdf->ezText($this->empresa->nombre . " - " . FS_PRESUPUESTOS . "  de venta del " . $this->desde . " al " . $this->hasta . ":\n\n", 14);
         $pdf_doc->pdf->ezText("Ninguna.\n\n", 14);
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

}
