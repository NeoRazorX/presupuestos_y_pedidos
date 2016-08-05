<?php

/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2014-2016  Carlos Garcia Gomez  neorazorx@gmail.com
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
require_once 'extras/phpmailer/class.phpmailer.php';
require_once 'extras/phpmailer/class.smtp.php';
require_model('articulo_proveedor.php');
require_model('cliente.php');
require_model('impuesto.php');
require_model('pedido_cliente.php');
require_model('pedido_proveedor.php');
require_model('presupuesto_cliente.php');
require_model('proveedor.php');

/**
 * Esta clase agrupa los procedimientos de imprimir/enviar presupuestos y pedidos.
 */
class imprimir_presu_pedi extends fs_controller
{
   public $articulo_proveedor;
   public $cliente;
   public $impresion;
   public $impuesto;
   public $pedido;
   public $presupuesto;
   public $proveedor;
   
   private $logo;
   private $numpaginas;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'imprimir', 'ventas', FALSE, FALSE);
   }
   
   protected function private_core()
   {
      $this->articulo_proveedor = new articulo_proveedor();
      $this->cliente = FALSE;
      $this->impuesto = new impuesto();
      $this->pedido = FALSE;
      $this->presupuesto = FALSE;
      $this->proveedor = FALSE;
      
      /// obtenemos los datos de configuración de impresión
      $this->impresion = array(
          'print_ref' => '1',
          'print_dto' => '1',
          'print_alb' => '0'
      );
      $fsvar = new fs_var();
      $this->impresion = $fsvar->array_get($this->impresion, FALSE);
      
      $this->logo = FALSE;
      if( file_exists('tmp/'.FS_TMP_NAME.'logo.png') )
      {
         $this->logo = 'tmp/'.FS_TMP_NAME.'logo.png';
      }
      else if( file_exists('tmp/'.FS_TMP_NAME.'logo.jpg') )
      {
         $this->logo = 'tmp/'.FS_TMP_NAME.'logo.jpg';
      }
      
      if( isset($_REQUEST['pedido_p']) AND isset($_REQUEST['id']) )
      {
         $ped = new pedido_proveedor();
         $this->pedido = $ped->get($_REQUEST['id']);
         if($this->pedido)
         {
            $proveedor = new proveedor();
            $this->proveedor = $proveedor->get($this->pedido->codproveedor);
         }
         
         if( isset($_POST['email']) )
         {
            $this->enviar_email_proveedor();
         }
         else
            $this->generar_pdf_pedido_proveedor();
      }
      else if( isset($_REQUEST['pedido']) AND isset($_REQUEST['id']) )
      {
         $ped = new pedido_cliente();
         $this->pedido = $ped->get($_REQUEST['id']);
         if($this->pedido)
         {
            $cliente = new cliente();
            $this->cliente = $cliente->get($this->pedido->codcliente);
         }
         
         if( isset($_POST['email']) )
         {
            $this->enviar_email('pedido');
         }
         else
            $this->generar_pdf_pedido();
      }
      else if( isset($_REQUEST['presupuesto']) AND isset($_REQUEST['id']) )
      {
         $pres = new presupuesto_cliente();
         $this->presupuesto = $pres->get($_REQUEST['id']);
         if($this->presupuesto)
         {
            $cliente = new cliente();
            $this->cliente = $cliente->get($this->presupuesto->codcliente);
         }
         
         if( isset($_POST['email']) )
         {
            $this->enviar_email('presupuesto');
         }
         else
            $this->generar_pdf_presupuesto();
      }
      
      $this->share_extensions();
   }
   
   private function share_extensions()
   {
      $extensiones = array(
          array(
              'name' => 'imprimir_pedido_proveedor',
              'page_from' => __CLASS__,
              'page_to' => 'compras_pedido',
              'type' => 'pdf',
              'text' => ucfirst(FS_PEDIDO).' simple',
              'params' => '&pedido_p=TRUE'
          ),
          array(
              'name' => 'email_pedido_proveedor',
              'page_from' => __CLASS__,
              'page_to' => 'compras_pedido',
              'type' => 'email',
              'text' => ucfirst(FS_PEDIDO).' simple',
              'params' => '&pedido_p=TRUE'
          ),
          array(
              'name' => 'imprimir_pedido',
              'page_from' => __CLASS__,
              'page_to' => 'ventas_pedido',
              'type' => 'pdf',
              'text' => '<span class="glyphicon glyphicon-print"></span>&nbsp; '.ucfirst(FS_PEDIDO).' simple',
              'params' => '&pedido=TRUE'
          ),
          array(
              'name' => 'imprimir_pedido_noval',
              'page_from' => __CLASS__,
              'page_to' => 'ventas_pedido',
              'type' => 'pdf',
              'text' => '<span class="glyphicon glyphicon-print"></span>&nbsp; '.ucfirst(FS_PEDIDO).' simple sin valorar',
              'params' => '&pedido=TRUE&noval=TRUE'
          ),
          array(
              'name' => 'email_pedido',
              'page_from' => __CLASS__,
              'page_to' => 'ventas_pedido',
              'type' => 'email',
              'text' => ucfirst(FS_PEDIDO).' simple',
              'params' => '&pedido=TRUE'
          ),
          array(
              'name' => 'imprimir_presupuesto',
              'page_from' => __CLASS__,
              'page_to' => 'ventas_presupuesto',
              'type' => 'pdf',
              'text' => '<span class="glyphicon glyphicon-print"></span>&nbsp; '.ucfirst(FS_PRESUPUESTO).' simple',
              'params' => '&presupuesto=TRUE'
          ),
          array(
              'name' => 'email_presupuesto',
              'page_from' => __CLASS__,
              'page_to' => 'ventas_presupuesto',
              'type' => 'email',
              'text' => ucfirst(FS_PRESUPUESTO).' simple',
              'params' => '&presupuesto=TRUE'
          )
      );
      foreach($extensiones as $ext)
      {
         $fsext = new fs_extension($ext);
         if( !$fsext->save() )
         {
            $this->new_error_msg('Error al guardar la extensión '.$ext['name']);
         }
      }
   }
   
   /**
    * Genera la parte de arriba de la página del pdf.
    * @param fs_pdf $pdf_doc
    * @param int $lppag
    */
   private function generar_pdf_cabecera(&$pdf_doc, &$lppag)
   {
      /// ¿Añadimos el logo?
      if($this->logo)
      {
         if( function_exists('imagecreatefromstring') )
         {
            $lppag -= 2; /// si metemos el logo, caben menos líneas
            
            if( substr( strtolower($this->logo), -4 ) == '.png' )
            {
               $pdf_doc->pdf->addPngFromFile($this->logo, 35, 740, 80, 80);
            }
            else
            {
               $pdf_doc->pdf->addJpegFromFile($this->logo, 35, 740, 80, 80);
            }
            
            $pdf_doc->pdf->ez['rightMargin'] = 40;
            $pdf_doc->pdf->ezText("<b>".$this->empresa->nombre."</b>", 12, array('justification' => 'right'));
            $pdf_doc->pdf->ezText(FS_CIFNIF.": ".$this->empresa->cifnif, 8, array('justification' => 'right'));
            
            $direccion = $this->empresa->direccion . "\n";
            if($this->empresa->apartado)
            {
               $direccion .= ucfirst(FS_APARTADO) . ': ' . $this->empresa->apartado . ' - ';
            }
            
            if($this->empresa->codpostal)
            {
               $direccion .= 'CP: ' . $this->empresa->codpostal . ' - ';
            }
            
            if($this->empresa->ciudad)
            {
               $direccion .= $this->empresa->ciudad . ' - ';
            }
            
            if($this->empresa->provincia)
            {
               $direccion .= '(' . $this->empresa->provincia . ')';
            }
            
            if($this->empresa->telefono)
            {
               $direccion .= "\nTeléfono: " . $this->empresa->telefono;
            }
            
            $pdf_doc->pdf->ezText($this->fix_html($direccion)."\n", 9, array('justification' => 'right'));
            $pdf_doc->set_y(750);
         }
         else
         {
            die('ERROR: no se encuentra la función imagecreatefromstring(). '
                    . 'Y por tanto no se puede usar el logotipo en los documentos.');
         }
      }
      else
      {
         $pdf_doc->pdf->ezText("<b>".$this->empresa->nombre."</b>", 16, array('justification' => 'center'));
         $pdf_doc->pdf->ezText(FS_CIFNIF.": ".$this->empresa->cifnif, 8, array('justification' => 'center'));
         
         $direccion = $this->empresa->direccion;
         if($this->empresa->apartado)
         {
            $direccion .= ' - '.ucfirst(FS_APARTADO).': ' . $this->empresa->apartado;
         }
            
         if($this->empresa->codpostal)
         {
            $direccion .= ' - CP: ' . $this->empresa->codpostal;
         }
         
         if($this->empresa->ciudad)
         {
            $direccion .= ' - ' . $this->empresa->ciudad;
         }
         
         if($this->empresa->provincia)
         {
            $direccion .= ' (' . $this->empresa->provincia . ')';
         }
         
         if($this->empresa->telefono)
         {
            $direccion .= ' - Teléfono: ' . $this->empresa->telefono;
         }
         
         $pdf_doc->pdf->ezText($this->fix_html($direccion), 9, array('justification' => 'center'));
      }
   }
   
   /**
    * Añade las líneas al documento pdf.
    * @param fs_pdf $pdf_doc
    * @param type $lineas
    * @param type $linea_actual
    * @param type $lppag
    * @param type $documento
    */
   private function generar_pdf_lineas(&$pdf_doc, &$lineas, &$linea_actual, $lppag, $documento)
   {
      /// calculamos el número de páginas
      if( !isset($this->numpaginas) )
      {
         $this->numpaginas = 0;
         $linea_a = 0;
         while( $linea_a < count($lineas) )
         {
            $lppag2 = $lppag;
            foreach($lineas as $i => $lin)
            {
               if($i >= $linea_a AND $i < $linea_a + $lppag2)
               {
                  $linea_size = 1;
                  $len = mb_strlen($lin->referencia.' '.$lin->descripcion);
                  while($len > 85)
                  {
                     $len -= 85;
                     $linea_size += 0.5;
                  }
                  
                  $aux = explode("\n", $lin->descripcion);
                  if( count($aux) > 1 )
                  {
                     $linea_size += 0.5 * ( count($aux) - 1);
                  }
                  
                  if($linea_size > 1)
                  {
                     $lppag2 -= $linea_size - 1;
                  }
               }
            }
            
            $linea_a += $lppag2;
            $this->numpaginas++;
         }
         
         if($this->numpaginas == 0)
         {
            $this->numpaginas = 1;
         }
      }
      
      if($this->impresion['print_dto'])
      {
         $this->impresion['print_dto'] = FALSE;
         
         /// leemos las líneas para ver si de verdad mostramos los descuentos
         foreach($lineas as $lin)
         {
            if($lin->dtopor != 0)
            {
               $this->impresion['print_dto'] = TRUE;
               break;
            }
         }
      }
      
      $dec_cantidad = 0;
      $multi_iva = FALSE;
      $multi_re = FALSE;
      $multi_irpf = FALSE;
      $iva = FALSE;
      $re = FALSE;
      $irpf = FALSE;
      /// leemos las líneas para ver si hay que mostrar los tipos de iva, re o irpf
      foreach($lineas as $i => $lin)
      {
         if( $lin->cantidad != intval($lin->cantidad) )
         {
            $dec_cantidad = 2;
         }
         
         if($iva === FALSE)
         {
            $iva = $lin->iva;
         }
         else if($lin->iva != $iva)
         {
            $multi_iva = TRUE;
         }
         
         if($re === FALSE)
         {
            $re = $lin->recargo;
         }
         else if($lin->recargo != $re)
         {
            $multi_re = TRUE;
         }
         
         if($irpf === FALSE)
         {
            $irpf = $lin->irpf;
         }
         else if($lin->irpf != $irpf)
         {
            $multi_irpf = TRUE;
         }
         
         /// restamos líneas al documento en función del tamaño de la descripción
         if($i >= $linea_actual AND $i < $linea_actual+$lppag)
         {
            $linea_size = 1;
            $len = mb_strlen($lin->referencia.' '.$lin->descripcion);
            while($len > 85)
            {
               $len -= 85;
               $linea_size += 0.5;
            }
            
            $aux = explode("\n", $lin->descripcion);
            if( count($aux) > 1 )
            {
               $linea_size += 0.5 * ( count($aux) - 1);
            }
            
            if($linea_size > 1)
            {
               $lppag -= $linea_size - 1;
            }
         }
      }
      
      /*
       * Creamos la tabla con las lineas del documento
       */
      $pdf_doc->new_table();
      $table_header = array(
          'cantidad' => '<b>Cant.</b>',
          'descripcion' => '<b>Ref. + Descripción</b>',
          'cantidad2' => '<b>Cant.</b>',
          'pvp' => '<b>PVP</b>',
      );
      
      if( get_class_name($lineas[$linea_actual]) == 'linea_pedido_proveedor' )
      {
         unset($table_header['cantidad2']);
         $table_header['descripcion'] = '<b>Ref. Prov. + Descripción</b>';
      }
      else
      {
         unset($table_header['cantidad']);
      }
      
      if( isset($_GET['noval']) )
      {
         unset($table_header['pvp']);
      }
      
      if( $this->impresion['print_dto'] AND !isset($_GET['noval']) )
      {
         $table_header['dto'] = '<b>Dto.</b>';
      }
      
      if( $multi_iva AND !isset($_GET['noval']) )
      {
         $table_header['iva'] = '<b>'.FS_IVA.'</b>';
      }
      
      if( $multi_re AND !isset($_GET['noval']) )
      {
         $table_header['re'] = '<b>R.E.</b>';
      }
      
      if( $multi_irpf AND !isset($_GET['noval']) )
      {
         $table_header['irpf'] = '<b>'.FS_IRPF.'</b>';
      }
      
      if( !isset($_GET['noval']) )
      {
         $table_header['importe'] = '<b>Importe</b>';
      }
      
      $pdf_doc->add_table_header($table_header);
      
      for($i = $linea_actual; (($linea_actual < ($lppag + $i)) AND ($linea_actual < count($lineas)));)
      {
         $descripcion = $this->fix_html($lineas[$linea_actual]->descripcion);
         if( !is_null($lineas[$linea_actual]->referencia) )
         {
            if( get_class_name($lineas[$linea_actual]) == 'linea_pedido_proveedor' )
            {
               $descripcion = '<b>'.$this->get_referencia_proveedor($lineas[$linea_actual]->referencia, $documento->codproveedor)
                       .'</b> '.$descripcion;
            }
            else
            {
               $descripcion = '<b>'.$lineas[$linea_actual]->referencia.'</b> '.$descripcion;
            }
         }
         
         $fila = array(
             'cantidad' => $this->show_numero($lineas[$linea_actual]->cantidad, $dec_cantidad),
             'cantidad2' => $this->show_numero($lineas[$linea_actual]->cantidad, $dec_cantidad),
             'descripcion' => $descripcion,
             'pvp' => $this->show_precio($lineas[$linea_actual]->pvpunitario, $documento->coddivisa, TRUE, FS_NF0_ART),
             'dto' => $this->show_numero($lineas[$linea_actual]->dtopor) . " %",
             'iva' => $this->show_numero($lineas[$linea_actual]->iva) . " %",
             're' => $this->show_numero($lineas[$linea_actual]->recargo) . " %",
             'irpf' => $this->show_numero($lineas[$linea_actual]->irpf) . " %",
             'importe' => $this->show_precio($lineas[$linea_actual]->pvptotal, $documento->coddivisa)
         );
         
         if($lineas[$linea_actual]->dtopor == 0)
         {
            $fila['dto'] = '';
         }
         
         if($lineas[$linea_actual]->recargo == 0)
         {
            $fila['re'] = '';
         }
         
         if($lineas[$linea_actual]->irpf == 0)
         {
            $fila['irpf'] = '';
         }
         
         if( get_class_name($lineas[$linea_actual]) != 'linea_pedido_proveedor' )
         {
            if( !$lineas[$linea_actual]->mostrar_cantidad )
            {
               $fila['cantidad'] = '';
               $fila['cantidad2'] = '';
            }
            
            if( !$lineas[$linea_actual]->mostrar_precio )
            {
               $fila['pvp'] = '';
               $fila['dto'] = '';
               $fila['iva'] = '';
               $fila['re'] = '';
               $fila['irpf'] = '';
               $fila['importe'] = '';
            }
         }
         
         $pdf_doc->add_table_row($fila);
         $linea_actual++;
      }
      
      $pdf_doc->save_table(
              array(
                  'fontSize' => 8,
                  'cols' => array(
                      'cantidad' => array('justification' => 'right'),
                      'cantidad2' => array('justification' => 'right'),
                      'pvp' => array('justification' => 'right'),
                      'dto' => array('justification' => 'right'),
                      'iva' => array('justification' => 'right'),
                      're' => array('justification' => 'right'),
                      'irpf' => array('justification' => 'right'),
                      'importe' => array('justification' => 'right')
                  ),
                  'width' => 520,
                  'shaded' => 1,
                  'shadeCol' => array(0.95, 0.95, 0.95),
                  'lineCol' => array(0.3, 0.3, 0.3),
              )
      );
      
      if( $linea_actual == count($lineas) )
      {
         if($documento->observaciones != '')
         {
            $pdf_doc->pdf->ezText("\n".$this->fix_html($documento->observaciones), 9);
         }
      }
   }
   
   private function generar_pdf_presupuesto($archivo = FALSE)
   {
      if( !$archivo )
      {
         /// desactivamos la plantilla HTML
         $this->template = FALSE;
      }
      
      $pdf_doc = new fs_pdf();
      $pdf_doc->pdf->addInfo('Title', ucfirst(FS_PRESUPUESTO).' '. $this->presupuesto->codigo);
      $pdf_doc->pdf->addInfo('Subject', ucfirst(FS_PRESUPUESTO).' de cliente ' . $this->presupuesto->codigo);
      $pdf_doc->pdf->addInfo('Author', $this->empresa->nombre);
      
      $lineas = $this->presupuesto->get_lineas();
      $lineas_iva = $this->get_lineas_iva($lineas);
      if($lineas)
      {
         $linea_actual = 0;
         $pagina = 1;
         
         /// imprimimos las páginas necesarias
         while( $linea_actual < count($lineas) )
         {
            $lppag = 35;
            
            /// salto de página
            if($linea_actual > 0)
            {
               $pdf_doc->pdf->ezNewPage();
            }
            
            $this->generar_pdf_cabecera($pdf_doc, $lppag);
            
            /*
             * Esta es la tabla con los datos del cliente:
             * Presupuesto:             Fecha:
             * Cliente:               CIF/NIF:
             * Dirección:           Teléfonos:
             */
            $pdf_doc->new_table();
            $pdf_doc->add_table_row(
               array(
                   'campo1' => "<b>".ucfirst(FS_PRESUPUESTO).":</b> ",
                   'dato1' => $this->presupuesto->codigo,
                   'campo2' => "<b>Fecha:</b> ".$this->presupuesto->fecha
               )
            );
            $pdf_doc->add_table_row(
               array(
                   'campo1' => "<b>Cliente:</b> ",
                   'dato1' => $this->fix_html($this->presupuesto->nombrecliente),
                   'campo2' => "<b>".$this->cliente->tipoidfiscal.":</b> ".$this->presupuesto->cifnif
               )
            );
            $direccion = $this->presupuesto->direccion;
            if($this->presupuesto->apartado)
            {
               $direccion .= ' - '.ucfirst(FS_APARTADO).': '.$this->presupuesto->apartado;
            }
            if($this->presupuesto->codpostal)
            {
               $direccion .= ' - CP: '.$this->presupuesto->codpostal;
            }
            $direccion .= ' - '.$this->presupuesto->ciudad.' ('.$this->presupuesto->provincia.')';
            $row = array(
                'campo1' => "<b>Dirección:</b>",
                'dato1' => $this->fix_html($direccion),
                'campo2' => ''
            );
            if($this->cliente->telefono1)
            {
               $row['campo2'] = "<b>Teléfonos:</b> ".$this->cliente->telefono1;
               if($this->cliente->telefono2)
               {
                  $row['campo2'] .= "\n".$this->cliente->telefono2;
                  $lppag -= 2;
               }
            }
            else if($this->cliente->telefono2)
            {
               $row['campo2'] = "<b>Teléfonos:</b> ".$this->cliente->telefono2;
            }
            $pdf_doc->add_table_row($row);
            if($this->empresa->codpais != 'ESP')
            {
               $pdf_doc->add_table_row(
                  array(
                      'campo1' => "<b>Régimen ".FS_IVA.":</b> ",
                      'dato1' => $this->cliente->regimeniva,
                      'campo2' => ''
                  )
               );
            }
            
            $pdf_doc->save_table(
               array(
                   'cols' => array(
                       'campo1' => array('width' => 90, 'justification' => 'right'),
                       'dato1' => array('justification' => 'left'),
                       'campo2' => array('justification' => 'right')
                   ),
                   'showLines' => 0,
                   'width' => 520,
                   'shaded' => 0
               )
            );
            $pdf_doc->pdf->ezText("\n", 10);
            
            /// lineas + observaciones
            $this->generar_pdf_lineas($pdf_doc, $lineas, $linea_actual, $lppag, $this->presupuesto);
            
            /// ¿Fecha de validez?
            if( $linea_actual == count($lineas) AND $this->presupuesto->finoferta)
            {
               $pdf_doc->pdf->ezText( "\n<b>".ucfirst(FS_PRESUPUESTO).' válido hasta:</b> '.$this->presupuesto->finoferta, 10 );
            }
            
            $pdf_doc->set_y(80);
            
            if( !isset($_GET['noval']) )
            {
               /*
                * Rellenamos la última tabla de la página:
                * 
                * Página            Neto    IVA   Total
                */
               $pdf_doc->new_table();
               $titulo = array('pagina' => '<b>Página</b>', 'neto' => '<b>Neto</b>',);
               $fila = array(
                   'pagina' => $pagina . '/' . $this->numpaginas,
                   'neto' => $this->show_precio($this->presupuesto->neto, $this->presupuesto->coddivisa),
               );
               $opciones = array(
                   'cols' => array(
                       'neto' => array('justification' => 'right'),
                   ),
                   'showLines' => 3,
                   'shaded' => 2,
                   'shadeCol2' => array(0.95, 0.95, 0.95),
                   'lineCol' => array(0.3, 0.3, 0.3),
                   'width' => 520
               );
               foreach($lineas_iva as $li)
               {
                  $imp = $this->impuesto->get($li['codimpuesto']);
                  if($imp)
                  {
                     $titulo['iva'.$li['iva']] = '<b>'.$imp->descripcion.'</b>';
                  }
                  else
                     $titulo['iva'.$li['iva']] = '<b>'.FS_IVA.' '.$li['iva'].'%</b>';
                  
                  $fila['iva'.$li['iva']] = $this->show_precio($li['totaliva'], $this->presupuesto->coddivisa);
                  
                  if($li['totalrecargo'] != 0)
                  {
                     $fila['iva'.$li['iva']] .= "\nR.E. ".$li['recargo']."%: ".$this->show_precio($li['totalrecargo'], $this->presupuesto->coddivisa);
                  }
                  
                  $opciones['cols']['iva'.$li['iva']] = array('justification' => 'right');
               }
               
               if($this->presupuesto->totalirpf != 0)
               {
                  $titulo['irpf'] = '<b>'.FS_IRPF.' '.$this->presupuesto->irpf.'%</b>';
                  $fila['irpf'] = $this->show_precio($this->presupuesto->totalirpf);
                  $opciones['cols']['irpf'] = array('justification' => 'right');
               }
               
               $titulo['liquido'] = '<b>Total</b>';
               $fila['liquido'] = $this->show_precio($this->presupuesto->total, $this->presupuesto->coddivisa);
               $opciones['cols']['liquido'] = array('justification' => 'right');
               $pdf_doc->add_table_header($titulo);
               $pdf_doc->add_table_row($fila);
               $pdf_doc->save_table($opciones);
            }
            
            $pagina++;
         }
      }
      else
      {
         $pdf_doc->pdf->ezText('¡'.ucfirst(FS_PRESUPUESTO).' sin líneas!', 20);
      }
      
      if($archivo)
      {
         if( !file_exists('tmp/'.FS_TMP_NAME.'enviar') )
         {
            mkdir('tmp/'.FS_TMP_NAME.'enviar');
         }
         
         $pdf_doc->save('tmp/'.FS_TMP_NAME.'enviar/'.$archivo);
      }
      else
         $pdf_doc->show(FS_PRESUPUESTO.'_'.$this->presupuesto->codigo.'.pdf');
   }
   
   private function generar_pdf_pedido_proveedor($archivo = FALSE)
   {
      if( !$archivo )
      {
         /// desactivamos la plantilla HTML
         $this->template = FALSE;
      }
      
      $pdf_doc = new fs_pdf();
      $pdf_doc->pdf->addInfo('Title', ucfirst(FS_PEDIDO).' '. $this->pedido->codigo);
      $pdf_doc->pdf->addInfo('Subject', ucfirst(FS_PEDIDO).' de proveedor ' . $this->pedido->codigo);
      $pdf_doc->pdf->addInfo('Author', $this->empresa->nombre);
      
      $lineas = $this->pedido->get_lineas();
      $lineas_iva = $this->get_lineas_iva($lineas);
      if($lineas)
      {
         $linea_actual = 0;
         $pagina = 1;
         
         /// imprimimos las páginas necesarias
         while( $linea_actual < count($lineas) )
         {
            $lppag = 35;
            
            /// salto de página
            if($linea_actual > 0)
            {
               $pdf_doc->pdf->ezNewPage();
            }
            
            $this->generar_pdf_cabecera($pdf_doc, $lppag);
            
            /*
             * Esta es la tabla con los datos del proveedor:
             * Pedido:                  Fecha:
             * Cliente:               CIF/NIF:
             */
            $pdf_doc->new_table();
            $pdf_doc->add_table_row(
               array(
                   'campo1' => "<b>".ucfirst(FS_PEDIDO).":</b>",
                   'dato1' => $this->pedido->codigo,
                   'campo2' => "<b>Fecha:</b> ".$this->pedido->fecha
               )
            );
            $pdf_doc->add_table_row(
               array(
                   'campo1' => "<b>Proveedor:</b>",
                   'dato1' => $this->fix_html($this->pedido->nombre),
                   'campo2' => "<b>".$this->proveedor->tipoidfiscal.":</b> ".$this->pedido->cifnif
               )
            );
            $pdf_doc->save_table(
               array(
                   'cols' => array(
                       'campo1' => array('width' => 90, 'justification' => 'right'),
                       'dato1' => array('justification' => 'left'),
                       'campo2' => array('justification' => 'right')
                   ),
                   'showLines' => 0,
                   'width' => 520,
                   'shaded' => 0
               )
            );
            $pdf_doc->pdf->ezText("\n", 10);
            
            /// lineas + observaciones
            $this->generar_pdf_lineas($pdf_doc, $lineas, $linea_actual, $lppag, $this->pedido);
            
            $pdf_doc->set_y(80);
            
            /*
             * Rellenamos la última tabla de la página:
             * 
             * Página            Neto    IVA   Total
             */
            $pdf_doc->new_table();
            $titulo = array('pagina' => '<b>Página</b>', 'neto' => '<b>Neto</b>',);
            $fila = array(
                'pagina' => $pagina . '/' . $this->numpaginas,
                'neto' => $this->show_precio($this->pedido->neto, $this->pedido->coddivisa),
            );
            $opciones = array(
                'cols' => array(
                    'neto' => array('justification' => 'right'),
                ),
                'showLines' => 3,
                   'shaded' => 2,
                   'shadeCol2' => array(0.95, 0.95, 0.95),
                   'lineCol' => array(0.3, 0.3, 0.3),
                   'width' => 520
            );
            foreach($lineas_iva as $li)
            {
               $imp = $this->impuesto->get($li['codimpuesto']);
               if($imp)
               {
                  $titulo['iva'.$li['iva']] = '<b>'.$imp->descripcion.'</b>';
               }
               else
                  $titulo['iva'.$li['iva']] = '<b>'.FS_IVA.' '.$li['iva'].'%</b>';
               
               $fila['iva'.$li['iva']] = $this->show_precio($li['totaliva'], $this->pedido->coddivisa);
               
               if($li['totalrecargo'] != 0)
               {
                  $fila['iva'.$li['iva']] .= "\nR.E. ".$li['recargo']."%: ".$this->show_precio($li['totalrecargo'], $this->pedido->coddivisa);
               }
               
               $opciones['cols']['iva'.$li['iva']] = array('justification' => 'right');
            }
            
            if($this->pedido->totalirpf != 0)
            {
               $titulo['irpf'] = '<b>'.FS_IRPF.' '.$this->pedido->irpf.'%</b>';
               $fila['irpf'] = $this->show_precio($this->pedido->totalirpf);
               $opciones['cols']['irpf'] = array('justification' => 'right');
            }
            
            $titulo['liquido'] = '<b>Total</b>';
            $fila['liquido'] = $this->show_precio($this->pedido->total, $this->pedido->coddivisa);
            $opciones['cols']['liquido'] = array('justification' => 'right');
            $pdf_doc->add_table_header($titulo);
            $pdf_doc->add_table_row($fila);
            $pdf_doc->save_table($opciones);
            
            $pagina++;
         }
      }
      else
      {
         $pdf_doc->pdf->ezText('¡'.ucfirst(FS_PEDIDO).' sin líneas!', 20);
      }
      
      if($archivo)
      {
         if( !file_exists('tmp/'.FS_TMP_NAME.'enviar') )
         {
            mkdir('tmp/'.FS_TMP_NAME.'enviar');
         }
         
         $pdf_doc->save('tmp/'.FS_TMP_NAME.'enviar/'.$archivo);
      }
      else
         $pdf_doc->show(FS_PEDIDO.'_compra_'.$this->pedido->codigo.'.pdf');
   }
   
   private function get_referencia_proveedor($ref, $codproveedor)
   {
      $artprov = $this->articulo_proveedor->get_by($ref, $codproveedor);
      if($artprov)
      {
         return $artprov->refproveedor;
      }
      else
         return $ref;
   }
   
   private function generar_pdf_pedido($archivo = FALSE)
   {
      if( !$archivo )
      {
         /// desactivamos la plantilla HTML
         $this->template = FALSE;
      }
      
      $pdf_doc = new fs_pdf();
      $pdf_doc->pdf->addInfo('Title', ucfirst(FS_PEDIDO).' '. $this->pedido->codigo);
      $pdf_doc->pdf->addInfo('Subject', ucfirst(FS_PEDIDO).' de cliente ' . $this->pedido->codigo);
      $pdf_doc->pdf->addInfo('Author', $this->empresa->nombre);
      
      $lineas = $this->pedido->get_lineas();
      $lineas_iva = $this->get_lineas_iva($lineas);
      if($lineas)
      {
         $linea_actual = 0;
         $pagina = 1;
         
         /// imprimimos las páginas necesarias
         while( $linea_actual < count($lineas) )
         {
            $lppag = 35;
            
            /// salto de página
            if($linea_actual > 0)
            {
               $pdf_doc->pdf->ezNewPage();
            }
            
            $this->generar_pdf_cabecera($pdf_doc, $lppag);
            
            /*
             * Esta es la tabla con los datos del cliente:
             * Pedido:                  Fecha:
             * Cliente:               CIF/NIF:
             * Dirección:           Teléfonos:
             */
            $pdf_doc->new_table();
            $pdf_doc->add_table_row(
               array(
                   'campo1' => "<b>".ucfirst(FS_PEDIDO).":</b> ",
                   'dato1' => $this->pedido->codigo,
                   'campo2' => "<b>Fecha:</b> ".$this->pedido->fecha
               )
            );
            $pdf_doc->add_table_row(
               array(
                   'campo1' => "<b>Cliente:</b> ",
                   'dato1' => $this->fix_html($this->pedido->nombrecliente),
                   'campo2' => "<b>".$this->cliente->tipoidfiscal.":</b> ".$this->pedido->cifnif
               )
            );
            $direccion = $this->pedido->direccion;
            if($this->pedido->apartado)
            {
               $direccion .= ' - '.ucfirst(FS_APARTADO).': '.$this->pedido->apartado;
            }
            if($this->pedido->codpostal)
            {
               $direccion .= ' - CP: '.$this->pedido->codpostal;
            }
            $direccion .= ' - '.$this->pedido->ciudad.' ('.$this->pedido->provincia.')';
            $row = array(
                'campo1' => "<b>Dirección:</b>",
                'dato1' => $this->fix_html($direccion),
                'campo2' => ''
            );
            if($this->cliente->telefono1)
            {
               $row['campo2'] = "<b>Teléfonos:</b> ".$this->cliente->telefono1;
               if($this->cliente->telefono2)
               {
                  $row['campo2'] .= "\n".$this->cliente->telefono2;
                  $lppag -= 2;
               }
            }
            else if($this->cliente->telefono2)
            {
               $row['campo2'] = "<b>Teléfonos:</b> ".$this->cliente->telefono2;
            }
            $pdf_doc->add_table_row($row);
            if($this->empresa->codpais != 'ESP')
            {
               $pdf_doc->add_table_row(
                  array(
                      'campo1' => "<b>Régimen ".FS_IVA.":</b> ",
                      'dato1' => $this->cliente->regimeniva,
                      'campo2' => ''
                  )
               );
            }
            
            $pdf_doc->save_table(
               array(
                   'cols' => array(
                       'campo1' => array('width' => 90, 'justification' => 'right'),
                       'dato1' => array('justification' => 'left'),
                       'campo2' => array('justification' => 'right')
                   ),
                   'showLines' => 0,
                   'width' => 520,
                   'shaded' => 0
               )
            );
            $pdf_doc->pdf->ezText("\n", 10);
            
            /// lineas + observaciones
            $this->generar_pdf_lineas($pdf_doc, $lineas, $linea_actual, $lppag, $this->pedido);
            
            $pdf_doc->set_y(80);
            
            if( isset($_GET['noval']) )
            {
               $pdf_doc->pdf->addText(10, 10, 8, $pdf_doc->center_text('Página '.$pagina . '/' . $this->numpaginas, 250) );
            }
            else
            {
               /*
                * Rellenamos la última tabla de la página:
                * 
                * Página            Neto    IVA   Total
                */
               $pdf_doc->new_table();
               $titulo = array('pagina' => '<b>Página</b>', 'neto' => '<b>Neto</b>',);
               $fila = array(
                   'pagina' => $pagina . '/' . $this->numpaginas,
                   'neto' => $this->show_precio($this->pedido->neto, $this->pedido->coddivisa),
               );
               $opciones = array(
                   'cols' => array(
                       'neto' => array('justification' => 'right'),
                   ),
                   'showLines' => 3,
                   'shaded' => 2,
                   'shadeCol2' => array(0.95, 0.95, 0.95),
                   'lineCol' => array(0.3, 0.3, 0.3),
                   'width' => 520
               );
               foreach($lineas_iva as $li)
               {
                  $imp = $this->impuesto->get($li['codimpuesto']);
                  if($imp)
                  {
                     $titulo['iva'.$li['iva']] = '<b>'.$imp->descripcion.'</b>';
                  }
                  else
                     $titulo['iva'.$li['iva']] = '<b>'.FS_IVA.' '.$li['iva'].'%</b>';
                  
                  $fila['iva'.$li['iva']] = $this->show_precio($li['totaliva'], $this->pedido->coddivisa);
                  
                  if($li['totalrecargo'] != 0)
                  {
                     $fila['iva'.$li['iva']] .= "\nR.E. ".$li['recargo']."%: ".$this->show_precio($li['totalrecargo'], $this->pedido->coddivisa);
                  }
                  
                  $opciones['cols']['iva'.$li['iva']] = array('justification' => 'right');
               }
               
               if($this->pedido->totalirpf != 0)
               {
                  $titulo['irpf'] = '<b>'.FS_IRPF.' '.$this->pedido->irpf.'%</b>';
                  $fila['irpf'] = $this->show_precio($this->pedido->totalirpf);
                  $opciones['cols']['irpf'] = array('justification' => 'right');
               }
               
               $titulo['liquido'] = '<b>Total</b>';
               $fila['liquido'] = $this->show_precio($this->pedido->total, $this->pedido->coddivisa);
               $opciones['cols']['liquido'] = array('justification' => 'right');
               $pdf_doc->add_table_header($titulo);
               $pdf_doc->add_table_row($fila);
               $pdf_doc->save_table($opciones);
            }
            
            $pagina++;
         }
      }
      else
      {
         $pdf_doc->pdf->ezText('¡'.ucfirst(FS_PEDIDO).' sin líneas!', 20);
      }
      
      if($archivo)
      {
         if( !file_exists('tmp/'.FS_TMP_NAME.'enviar') )
         {
            mkdir('tmp/'.FS_TMP_NAME.'enviar');
         }
         
         $pdf_doc->save('tmp/'.FS_TMP_NAME.'enviar/'.$archivo);
      }
      else
      {
         $pdf_doc->show(FS_PEDIDO.'_'.$this->pedido->codigo.'.pdf');
      }
   }
   
   private function enviar_email_proveedor()
   {
      if( $this->empresa->can_send_mail() )
      {
         if( $_POST['email'] != $this->proveedor->email AND isset($_POST['guardar']) )
         {
            $this->proveedor->email = $_POST['email'];
            $this->proveedor->save();
         }
         
         $filename = 'pedido_'.$this->pedido->codigo.'.pdf';
         $this->generar_pdf_pedido_proveedor($filename);
         
         if( file_exists('tmp/'.FS_TMP_NAME.'enviar/'.$filename) )
         {
            $mail = $this->empresa->new_mail();
            $mail->FromName = $this->user->get_agente_fullname();
            $mail->addReplyTo($_POST['de'], $mail->FromName);
            
            $mail->addAddress($_POST['email'], $this->proveedor->razonsocial);
            if($_POST['email_copia'])
            {
               if( isset($_POST['cco']) )
               {
                  $mail->addBCC($_POST['email_copia'], $this->proveedor->razonsocial);
               }
               else
               {
                  $mail->addCC($_POST['email_copia'], $this->proveedor->razonsocial);
               }
            }
            
            $mail->Subject = $this->empresa->nombre . ': Mi '.FS_PEDIDO.' '.$this->pedido->codigo;
            $mail->AltBody = $_POST['mensaje'];
            $mail->msgHTML( nl2br($_POST['mensaje']) );
            $mail->isHTML(TRUE);
            
            $mail->addAttachment('tmp/'.FS_TMP_NAME.'enviar/'.$filename);
            if( is_uploaded_file($_FILES['adjunto']['tmp_name']) )
            {
               $mail->addAttachment($_FILES['adjunto']['tmp_name'], $_FILES['adjunto']['name']);
            }
            
            if( $mail->smtpConnect($this->empresa->smtp_options()) )
            {
               if( $mail->send() )
               {
                  $this->new_message('Mensaje enviado correctamente.');
                  $this->empresa->save_mail($mail);
               }
               else
                  $this->new_error_msg("Error al enviar el email: " . $mail->ErrorInfo);
            }
            else
               $this->new_error_msg("Error al enviar el email: " . $mail->ErrorInfo);
            
            unlink('tmp/'.FS_TMP_NAME.'enviar/'.$filename);
         }
         else
            $this->new_error_msg('Imposible generar el PDF.');
      }
   }
   
   private function enviar_email($doc)
   {
      if( $this->empresa->can_send_mail() )
      {
         if( $_POST['email'] != $this->cliente->email AND isset($_POST['guardar']) )
         {
            $this->cliente->email = $_POST['email'];
            $this->cliente->save();
         }
         
         if($doc == 'presupuesto')
         {
            $filename = 'presupuesto_'.$this->presupuesto->codigo.'.pdf';
            $this->generar_pdf_presupuesto($filename);
         }
         else
         {
            $filename = 'pedido_'.$this->pedido->codigo.'.pdf';
            $this->generar_pdf_pedido($filename);
         }
         
         if( file_exists('tmp/'.FS_TMP_NAME.'enviar/'.$filename) )
         {
            $mail = $this->empresa->new_mail();
            $mail->FromName = $this->user->get_agente_fullname();
            $mail->addReplyTo($_POST['de'], $mail->FromName);
            
            $mail->addAddress($_POST['email'], $this->cliente->razonsocial);
            if($_POST['email_copia'])
            {
               if( isset($_POST['cco']) )
               {
                  $mail->addBCC($_POST['email_copia'], $this->cliente->razonsocial);
               }
               else
               {
                  $mail->addCC($_POST['email_copia'], $this->cliente->razonsocial);
               }
            }
            
            if($doc == 'presupuesto')
            {
               $mail->Subject = $this->empresa->nombre . ': Su '.FS_PRESUPUESTO.' '.$this->presupuesto->codigo;
            }
            else
            {
               $mail->Subject = $this->empresa->nombre . ': Su '.FS_PEDIDO.' '.$this->pedido->codigo;
            }
            
            $mail->AltBody = $_POST['mensaje'];
            $mail->msgHTML( nl2br($_POST['mensaje']) );
            $mail->isHTML(TRUE);
            
            $mail->addAttachment('tmp/'.FS_TMP_NAME.'enviar/'.$filename);
            if( is_uploaded_file($_FILES['adjunto']['tmp_name']) )
            {
               $mail->addAttachment($_FILES['adjunto']['tmp_name'], $_FILES['adjunto']['name']);
            }
            
            if( $mail->smtpConnect($this->empresa->smtp_options()) )
            {
               if( $mail->send() )
               {
                  $this->new_message('Mensaje enviado correctamente.');
                  
                  /// nos guardamos la fecha del envío
                  if($doc == 'presupuesto')
                  {
                     $this->presupuesto->femail = $this->today();
                     $this->presupuesto->save();
                  }
                  else
                  {
                     $this->pedido->femail = $this->today();
                     $this->pedido->save();
                  }
                  
                  $this->empresa->save_mail($mail);
               }
               else
                  $this->new_error_msg("Error al enviar el email: " . $mail->ErrorInfo);
            }
            else
               $this->new_error_msg("Error al enviar el email: " . $mail->ErrorInfo);
            
            unlink('tmp/'.FS_TMP_NAME.'enviar/'.$filename);
         }
         else
            $this->new_error_msg('Imposible generar el PDF.');
      }
   }
   
   private function fix_html($txt)
   {
      $newt = str_replace('&lt;', '<', $txt);
      $newt = str_replace('&gt;', '>', $newt);
      $newt = str_replace('&quot;', '"', $newt);
      $newt = str_replace('&#39;', "'", $newt);
      return $newt;
   }
   
   private function get_lineas_iva($lineas)
   {
      $retorno = array();
      $lineasiva = array();
      
      foreach($lineas as $lin)
      {
         if( isset($lineasiva[$lin->codimpuesto]) )
         {
            if($lin->recargo > $lineasiva[$lin->codimpuesto]['recargo'])
            {
               $lineasiva[$lin->codimpuesto]['recargo'] = $lin->recargo;
            }
            
            $lineasiva[$lin->codimpuesto]['neto'] += $lin->pvptotal;
            $lineasiva[$lin->codimpuesto]['totaliva'] += ($lin->pvptotal*$lin->iva)/100;
            $lineasiva[$lin->codimpuesto]['totalrecargo'] += ($lin->pvptotal*$lin->recargo)/100;
            $lineasiva[$lin->codimpuesto]['totallinea'] = $lineasiva[$lin->codimpuesto]['neto']
                    + $lineasiva[$lin->codimpuesto]['totaliva'] + $lineasiva[$lin->codimpuesto]['totalrecargo'];
         }
         else
         {
            $lineasiva[$lin->codimpuesto] = array(
                'codimpuesto' => $lin->codimpuesto,
                'iva' => $lin->iva,
                'recargo' => $lin->recargo,
                'neto' => $lin->pvptotal,
                'totaliva' => ($lin->pvptotal*$lin->iva)/100,
                'totalrecargo' => ($lin->pvptotal*$lin->recargo)/100,
                'totallinea' => 0
            );
            $lineasiva[$lin->codimpuesto]['totallinea'] = $lineasiva[$lin->codimpuesto]['neto']
                    + $lineasiva[$lin->codimpuesto]['totaliva'] + $lineasiva[$lin->codimpuesto]['totalrecargo'];
         }
      }
      
      foreach($lineasiva as $lin)
      {
         $retorno[] = $lin;
      }
      
      return $retorno;
   }
}
