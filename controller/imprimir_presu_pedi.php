<?php
/*
 * This file is part of presupuestos_y_pedidos
 * Copyright (C) 2014-2017  Carlos Garcia Gomez  neorazorx@gmail.com
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
require_model('cuenta_banco.php');
require_model('cuenta_banco_cliente.php');
require_model('forma_pago.php');
require_model('impuesto.php');
require_model('pais.php');
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
   public $documento;
   public $impresion;
   public $impuesto;
   public $proveedor;
   
   private $numpaginas;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'imprimir', 'ventas', FALSE, FALSE);
   }
   
   protected function private_core()
   {
      $this->articulo_proveedor = new articulo_proveedor();
      $this->cliente = FALSE;
      $this->documento = FALSE;
      $this->impuesto = new impuesto();
      $this->proveedor = FALSE;
      
      /// obtenemos los datos de configuración de impresión
      $this->impresion = array(
          'print_ref' => '1',
          'print_dto' => '1',
          'print_alb' => '0',
          'print_formapago' => '1'
      );
      $fsvar = new fs_var();
      $this->impresion = $fsvar->array_get($this->impresion, FALSE);
      
      if( isset($_REQUEST['pedido_p']) AND isset($_REQUEST['id']) )
      {
         $ped = new pedido_proveedor();
         $this->documento = $ped->get($_REQUEST['id']);
         if($this->documento)
         {
            $proveedor = new proveedor();
            $this->proveedor = $proveedor->get($this->documento->codproveedor);
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
         $this->documento = $ped->get($_REQUEST['id']);
         if($this->documento)
         {
            $cliente = new cliente();
            $this->cliente = $cliente->get($this->documento->codcliente);
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
         $this->documento = $pres->get($_REQUEST['id']);
         if($this->documento)
         {
            $cliente = new cliente();
            $this->cliente = $cliente->get($this->documento->codcliente);
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
    * Añade las líneas al documento pdf.
    * @param fs_pdf $pdf_doc
    * @param type $lineas
    * @param type $linea_actual
    * @param type $lppag
    */
   private function generar_pdf_lineas(&$pdf_doc, &$lineas, &$linea_actual, &$lppag)
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
          'pvp' => '<b>Precio</b>',
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
         $descripcion = $pdf_doc->fix_html($lineas[$linea_actual]->descripcion);
         if( !is_null($lineas[$linea_actual]->referencia) )
         {
            if( get_class_name($lineas[$linea_actual]) == 'linea_pedido_proveedor' )
            {
               $descripcion = '<b>'.$this->get_referencia_proveedor($lineas[$linea_actual]->referencia, $this->documento->codproveedor)
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
             'pvp' => $this->show_precio($lineas[$linea_actual]->pvpunitario, $this->documento->coddivisa, TRUE, FS_NF0_ART),
             'dto' => $this->show_numero($lineas[$linea_actual]->dtopor) . " %",
             'iva' => $this->show_numero($lineas[$linea_actual]->iva) . " %",
             're' => $this->show_numero($lineas[$linea_actual]->recargo) . " %",
             'irpf' => $this->show_numero($lineas[$linea_actual]->irpf) . " %",
             'importe' => $this->show_precio($lineas[$linea_actual]->pvptotal, $this->documento->coddivisa)
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
      
      /// ¿Última página?
      if( $linea_actual == count($lineas) )
      {
         if($this->documento->observaciones != '')
         {
            $pdf_doc->pdf->ezText("\n".$pdf_doc->fix_html($this->documento->observaciones), 9);
         }
      }
   }
   
   private function generar_pdf_datos_cliente(&$pdf_doc, &$lppag)
   {
      $tipo_doc = ucfirst(FS_PRESUPUESTO);
      if( get_class_name($this->documento) == 'pedido_cliente' )
      {
         $tipo_doc = ucfirst(FS_PEDIDO);
      }
      
      $tipoidfiscal = FS_CIFNIF;
      if($this->cliente)
      {
         $tipoidfiscal = $this->cliente->tipoidfiscal;
      }
      
      /*
       * Esta es la tabla con los datos del cliente:
       * Presupuesto:             Fecha:
       * Cliente:               CIF/NIF:
       * Dirección:           Teléfonos:
       */
      $pdf_doc->new_table();
      $pdf_doc->add_table_row(
              array(
                  'campo1' => "<b>".$tipo_doc.":</b> ",
                  'dato1' => $this->documento->codigo,
                  'campo2' => "<b>Fecha:</b> ".$this->documento->fecha
              )
      );
      
      $pdf_doc->add_table_row(
              array(
                  'campo1' => "<b>Cliente:</b> ",
                  'dato1' => $pdf_doc->fix_html($this->documento->nombrecliente),
                  'campo2' => "<b>".$tipoidfiscal.":</b> ".$this->documento->cifnif
              )
      );
      
      $direccion = $this->documento->direccion;
      if($this->documento->apartado)
      {
         $direccion .= ' - '.ucfirst(FS_APARTADO).': '.$this->documento->apartado;
      }
      if($this->documento->codpostal)
      {
         $direccion .= ' - CP: '.$this->documento->codpostal;
      }
      $direccion .= ' - '.$this->documento->ciudad;
      if($this->documento->provincia)
      {
         $direccion .= ' ('.$this->documento->provincia.')';
      }
      if($this->documento->codpais != $this->empresa->codpais)
      {
         $pais0 = new pais();
         $pais = $pais0->get($this->documento->codpais);
         if($pais)
         {
            $direccion .= ' '.$pais->nombre;
         }
      }
      $row = array(
          'campo1' => "<b>Dirección:</b>",
          'dato1' => $pdf_doc->fix_html($direccion),
          'campo2' => ''
      );
      
      if(!$this->cliente)
      {
         /// nada
      }
      else if($this->cliente->telefono1)
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
   }
   
   private function generar_pdf_totales(&$pdf_doc, &$lineas_iva, $pagina)
   {
      if( isset($_GET['noval']) )
      {
         $pdf_doc->pdf->addText(10, 10, 8, $pdf_doc->center_text('Página '.$pagina.'/'.$this->numpaginas, 250) );
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
             'neto' => $this->show_precio($this->documento->neto, $this->documento->coddivisa),
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
            
            $fila['iva'.$li['iva']] = $this->show_precio($li['totaliva'], $this->documento->coddivisa);
            
            if($li['totalrecargo'] != 0)
            {
               $fila['iva'.$li['iva']] .= "\nR.E. ".$li['recargo']."%: ".$this->show_precio($li['totalrecargo'], $this->documento->coddivisa);
            }
               
            $opciones['cols']['iva'.$li['iva']] = array('justification' => 'right');
         }
         
         if($this->documento->totalirpf != 0)
         {
            $titulo['irpf'] = '<b>'.FS_IRPF.' '.$this->documento->irpf.'%</b>';
            $fila['irpf'] = $this->show_precio($this->documento->totalirpf);
            $opciones['cols']['irpf'] = array('justification' => 'right');
         }
         
         $titulo['liquido'] = '<b>Total</b>';
         $fila['liquido'] = $this->show_precio($this->documento->total, $this->documento->coddivisa);
         $opciones['cols']['liquido'] = array('justification' => 'right');
         
         $pdf_doc->add_table_header($titulo);
         $pdf_doc->add_table_row($fila);
         $pdf_doc->save_table($opciones);
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
      $pdf_doc->pdf->addInfo('Title', ucfirst(FS_PRESUPUESTO).' '. $this->documento->codigo);
      $pdf_doc->pdf->addInfo('Subject', ucfirst(FS_PRESUPUESTO).' de cliente ' . $this->documento->codigo);
      $pdf_doc->pdf->addInfo('Author', $this->empresa->nombre);
      
      $lineas = $this->documento->get_lineas();
      $lineas_iva = $pdf_doc->get_lineas_iva($lineas);
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
            
            $pdf_doc->generar_pdf_cabecera($this->empresa, $lppag);
            $this->generar_pdf_datos_cliente($pdf_doc, $lppag);
            $this->generar_pdf_lineas($pdf_doc, $lineas, $linea_actual, $lppag);
            
            /// ¿Fecha de validez?
            if( $linea_actual == count($lineas) )
            {
               $texto_pago = '';
               
               if($this->documento->finoferta)
               {
                  $texto_pago = "\n<b>".ucfirst(FS_PRESUPUESTO).' válido hasta:</b> '.$this->documento->finoferta;
               }
               
               if($this->impresion['print_formapago'])
               {
                  $fp0 = new forma_pago();
                  $forma_pago = $fp0->get($this->documento->codpago);
                  if($forma_pago)
                  {
                     $texto_pago .= "\n<b>Forma de pago</b>: ".$forma_pago->descripcion;
                     
                     if(!$forma_pago->imprimir)
                     {
                        /// nada
                     }
                     else if($forma_pago->domiciliado)
                     {
                        $cbc0 = new cuenta_banco_cliente();
                        $encontrada = FALSE;
                        foreach($cbc0->all_from_cliente($this->documento->codcliente) as $cbc)
                        {
                           $texto_pago .= "\n<b>Domiciliado en</b>: ";
                           if($cbc->iban)
                           {
                              $texto_pago .= $cbc->iban(TRUE);
                           }
                           
                           if($cbc->swift)
                           {
                              $texto_pago .= "\n<b>SWIFT/BIC</b>: ".$cbc->swift;
                           }
                           $encontrada = TRUE;
                           break;
                        }
                        if(!$encontrada)
                        {
                           $texto_pago .= "\n<b>El cliente no tiene cuenta bancaria asignada.</b>";
                        }
                     }
                     else if($forma_pago->codcuenta)
                     {
                        $cb0 = new cuenta_banco();
                        $cuenta_banco = $cb0->get($forma_pago->codcuenta);
                        if($cuenta_banco)
                        {
                           if($cuenta_banco->iban)
                           {
                              $texto_pago .= "\n<b>IBAN</b>: ".$cuenta_banco->iban(TRUE);
                           }
                           
                           if($cuenta_banco->swift)
                           {
                              $texto_pago .= "\n<b>SWIFT o BIC</b>: ".$cuenta_banco->swift;
                           }
                        }
                     }
                  }
               }
               
               $pdf_doc->pdf->ezText($texto_pago, 9);
            }
            
            $pdf_doc->set_y(80);
            $this->generar_pdf_totales($pdf_doc, $lineas_iva, $pagina);
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
      {
         $pdf_doc->show(FS_PRESUPUESTO.'_'.$this->documento->codigo.'.pdf');
      }
   }
   
   private function generar_pdf_pedido_proveedor($archivo = FALSE)
   {
      if( !$archivo )
      {
         /// desactivamos la plantilla HTML
         $this->template = FALSE;
      }
      
      $pdf_doc = new fs_pdf();
      $pdf_doc->pdf->addInfo('Title', ucfirst(FS_PEDIDO).' '. $this->documento->codigo);
      $pdf_doc->pdf->addInfo('Subject', ucfirst(FS_PEDIDO).' de proveedor ' . $this->documento->codigo);
      $pdf_doc->pdf->addInfo('Author', $this->empresa->nombre);
      
      $lineas = $this->documento->get_lineas();
      $lineas_iva = $pdf_doc->get_lineas_iva($lineas);
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
            
            $pdf_doc->generar_pdf_cabecera($this->empresa, $lppag);
            
            /*
             * Esta es la tabla con los datos del proveedor:
             * Pedido:                  Fecha:
             * Cliente:               CIF/NIF:
             */
            $pdf_doc->new_table();
            $pdf_doc->add_table_row(
               array(
                   'campo1' => "<b>".ucfirst(FS_PEDIDO).":</b>",
                   'dato1' => $this->documento->codigo,
                   'campo2' => "<b>Fecha:</b> ".$this->documento->fecha
               )
            );
            
            $tipoidfiscal = FS_CIFNIF;
            if($this->proveedor)
            {
               $tipoidfiscal = $this->proveedor->tipoidfiscal;
            }
            $pdf_doc->add_table_row(
               array(
                   'campo1' => "<b>Proveedor:</b>",
                   'dato1' => $pdf_doc->fix_html($this->documento->nombre),
                   'campo2' => "<b>".$tipoidfiscal.":</b> ".$this->documento->cifnif
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
            $this->generar_pdf_lineas($pdf_doc, $lineas, $linea_actual, $lppag);
            
            $pdf_doc->set_y(80);
            $this->generar_pdf_totales($pdf_doc, $lineas_iva, $pagina);
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
         $pdf_doc->show(FS_PEDIDO.'_compra_'.$this->documento->codigo.'.pdf');
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
      $pdf_doc->pdf->addInfo('Title', ucfirst(FS_PEDIDO).' '. $this->documento->codigo);
      $pdf_doc->pdf->addInfo('Subject', ucfirst(FS_PEDIDO).' de cliente ' . $this->documento->codigo);
      $pdf_doc->pdf->addInfo('Author', $this->empresa->nombre);
      
      $lineas = $this->documento->get_lineas();
      $lineas_iva = $pdf_doc->get_lineas_iva($lineas);
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
            
            $pdf_doc->generar_pdf_cabecera($this->empresa, $lppag);
            $this->generar_pdf_datos_cliente($pdf_doc, $lppag);
            $this->generar_pdf_lineas($pdf_doc, $lineas, $linea_actual, $lppag);
            
            $pdf_doc->set_y(80);
            $this->generar_pdf_totales($pdf_doc, $lineas_iva, $pagina);
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
         $pdf_doc->show(FS_PEDIDO.'_'.$this->documento->codigo.'.pdf');
      }
   }
   
   private function enviar_email_proveedor()
   {
      if( $this->empresa->can_send_mail() )
      {
         if($this->proveedor)
         {
            if( $_POST['email'] != $this->proveedor->email AND isset($_POST['guardar']) )
            {
               $this->proveedor->email = $_POST['email'];
               $this->proveedor->save();
            }
         }
         
         $filename = 'pedido_'.$this->documento->codigo.'.pdf';
         $this->generar_pdf_pedido_proveedor($filename);
         $razonsocial = $this->documento->nombre;
         
         if( file_exists('tmp/'.FS_TMP_NAME.'enviar/'.$filename) )
         {
            $mail = $this->empresa->new_mail();
            $mail->FromName = $this->user->get_agente_fullname();
            
            if($_POST['de'] != $mail->From)
            {
               $mail->addReplyTo($_POST['de'], $mail->FromName);
            }
            
            $mail->addAddress($_POST['email'], $razonsocial);
            if($_POST['email_copia'])
            {
               if( isset($_POST['cco']) )
               {
                  $mail->addBCC($_POST['email_copia'], $razonsocial);
               }
               else
               {
                  $mail->addCC($_POST['email_copia'], $razonsocial);
               }
            }
            
            $mail->Subject = $this->empresa->nombre . ': Mi '.FS_PEDIDO.' '.$this->documento->codigo;
            if( $this->is_html($_POST['mensaje']) )
            {
               $mail->AltBody = strip_tags($_POST['mensaje']);
               $mail->msgHTML($_POST['mensaje']);
               $mail->isHTML(TRUE);
            }
            else
            {
               $mail->Body = $_POST['mensaje'];
            }
            
            $mail->addAttachment('tmp/'.FS_TMP_NAME.'enviar/'.$filename);
            if( is_uploaded_file($_FILES['adjunto']['tmp_name']) )
            {
               $mail->addAttachment($_FILES['adjunto']['tmp_name'], $_FILES['adjunto']['name']);
            }
            
            if( $this->empresa->mail_connect($mail) )
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
         if($doc == 'presupuesto')
         {
            $filename = 'presupuesto_'.$this->documento->codigo.'.pdf';
            $this->generar_pdf_presupuesto($filename);
         }
         else
         {
            $filename = 'pedido_'.$this->documento->codigo.'.pdf';
            $this->generar_pdf_pedido($filename);
         }
         
         $razonsocial = $this->documento->nombrecliente;
         if($this->cliente)
         {
            if( $_POST['email'] != $this->cliente->email AND isset($_POST['guardar']) )
            {
               $this->cliente->email = $_POST['email'];
               $this->cliente->save();
            }
         }
         
         if( file_exists('tmp/'.FS_TMP_NAME.'enviar/'.$filename) )
         {
            $mail = $this->empresa->new_mail();
            $mail->FromName = $this->user->get_agente_fullname();
            
            if($_POST['de'] != $mail->From)
            {
               $mail->addReplyTo($_POST['de'], $mail->FromName);
            }
            
            $mail->addAddress($_POST['email'], $razonsocial);
            if($_POST['email_copia'])
            {
               if( isset($_POST['cco']) )
               {
                  $mail->addBCC($_POST['email_copia'], $razonsocial);
               }
               else
               {
                  $mail->addCC($_POST['email_copia'], $razonsocial);
               }
            }
            
            if($doc == 'presupuesto')
            {
               $mail->Subject = $this->empresa->nombre . ': Su '.FS_PRESUPUESTO.' '.$this->documento->codigo;
            }
            else
            {
               $mail->Subject = $this->empresa->nombre . ': Su '.FS_PEDIDO.' '.$this->documento->codigo;
            }
            
            if( $this->is_html($_POST['mensaje']) )
            {
               $mail->AltBody = strip_tags($_POST['mensaje']);
               $mail->msgHTML($_POST['mensaje']);
               $mail->isHTML(TRUE);
            }
            else
            {
               $mail->Body = $_POST['mensaje'];
            }
            
            $mail->addAttachment('tmp/'.FS_TMP_NAME.'enviar/'.$filename);
            if( is_uploaded_file($_FILES['adjunto']['tmp_name']) )
            {
               $mail->addAttachment($_FILES['adjunto']['tmp_name'], $_FILES['adjunto']['name']);
            }
            
            if( $this->empresa->mail_connect($mail) )
            {
               if( $mail->send() )
               {
                  $this->new_message('Mensaje enviado correctamente.');
                  
                  /// nos guardamos la fecha del envío
                  $this->documento->femail = $this->today();
                  $this->documento->save();
                  
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
   
   public function is_html($txt)
   {
      if( stripos($txt, '<html') === FALSE )
      {
         return FALSE;
      }
      else
      {
         return TRUE;
      }
   }
}
