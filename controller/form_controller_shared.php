<?php

/*
 * This file is part of presupuestos_y_pedidos
 * Copyright (C) 2014-2017  Carlos Garcia Gomez  neorazorx@gmail.com
 * Copyright (C) 2014-2015  Francesc Pineda Segarra  shawe.ewahs@gmail.com
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

/**
 * Procedures and shared utilities
 *
 * @author Artex Trading sa (2017) <jcuello@artextrading.com>
 */

trait form_controller {
   public $agente;
   public $almacen;
   public $divisa;
   public $ejercicio;
   public $fabricante;
   public $familia;
   public $forma_pago;
   public $impuesto;
   public $nuevo_pedido_url;
   public $pedido;
   public $serie;
   public $versiones;
   
   private function get_documento($modelo, $campo_id, &$documento) {
      if (isset($_POST[$campo_id])) {
         $documento = $modelo->get($_POST[$campo_id]);
         $this->modificar();
         return TRUE;
      } 

      if (isset($_GET['id'])) {
         $documento = $modelo->get($_GET['id']);
         return TRUE;
      }
      
      return FALSE;
   }
   
   private function delete_documento($documento) {
      if ($documento->delete())
         $message = "El documento se ha borrado.";
      else
         $message = "¡Imposible borrar el documento!"; 
      
      $this->new_error_msg($message);
   }
   
   private function generar_lineas_documento($documento, $origen, $linea_model, &$trazabilidad) {
      $result = TRUE;
      $art0 = new articulo();
      foreach ($origen->get_lineas() as $linea_origen) {
         $linea = new $linea_model();
            
         $linea->cantidad = $linea_origen->cantidad;
         $linea->codimpuesto = $linea_origen->codimpuesto;
         $linea->descripcion = $linea_origen->descripcion;
         $linea->dtopor = $linea_origen->dtopor;
         $linea->irpf = $linea_origen->irpf;
         $linea->iva = $linea_origen->iva;
         $linea->pvpsindto = $linea_origen->pvpsindto;
         $linea->pvptotal = $linea_origen->pvptotal;
         $linea->pvpunitario = $linea_origen->pvpunitario;
         $linea->recargo = $linea_origen->recargo;
         $linea->referencia = $linea_origen->referencia;
         $linea->codcombinacion = $linea_origen->codcombinacion;

         switch ($linea_model) {
            case 'linea_pedido_cliente': {
               $linea->idlineapresupuesto = $linea_origen->idlinea;
               $linea->idpresupuesto = $linea_origen->idpresupuesto;
               $linea->idpedido = $documento->idpedido;
               $linea->orden = $linea_origen->orden;
               $linea->mostrar_cantidad = $linea_origen->mostrar_cantidad;
               $linea->mostrar_precio = $linea_origen->mostrar_precio;
               $cantidad = 0;
               $costemedio = FALSE;
               break;
            }
            
            case 'linea_albaran_proveedor': {
               $linea->idlineapedido = $linea_origen->idlinea;
               $linea->idpedido = $linea_origen->idpedido;
               $linea->idalbaran = $documento->idalbaran;
               $cantidad = $linea_origen->cantidad;
               $costemedio = isset($_POST['costemedio']);
               break;
            }
            
            case 'linea_albaran_cliente': {
               $linea->idlineapedido = $linea_origen->idlinea;
               $linea->idpedido = $linea_origen->idpedido;
               $linea->idalbaran = $documento->idalbaran;
               $linea->orden = $linea_origen->orden;
               $linea->mostrar_cantidad = $linea_origen->mostrar_cantidad;
               $linea->mostrar_precio = $linea_origen->mostrar_precio;

               $cantidad = 0 - $linea_origen->cantidad;
               $costemedio = FALSE;
               break;
            }
            
            default: {
               $this->new_error_msg("¡Falta informar el tipo de linea a crear! ");
               $result = FALSE;
               break;
            }
         }
                  
         if ($linea->save()) {
            /// añadimos al stock si hay variación del stock
            if ($cantidad == 0)
               continue;
            
            if ($linea->referencia AND isset($_POST['stock'])) {
               $articulo = $art0->get($linea->referencia);
               if ($articulo) {
                  $articulo->sum_stock($documento->codalmacen, $cantidad, $costemedio, $linea_origen->codcombinacion);
                  if ($articulo->trazabilidad)
                     $trazabilidad = TRUE;
               }
            }
         } 
         else {
            $this->new_error_msg("¡Imposible guardar la línea del artículo " . $linea->referencia . "! ");
            $result = FALSE;
         }
      }      
      return $result;
   }   
      
   private function generar_documento($model, $fecha, $origen, $grabar_origen) {
      $documento = new $model;
      
      // Calculamos la fecha del documento
      if (isset($_POST[$fecha]))
         $documento->fecha = $_POST[$fecha];
      
      // Calculamos el ejercicio
      $eje0 = $this->ejercicio->get_by_fecha($documento->fecha, FALSE);
      if ($eje0)
         $documento->codejercicio = $eje0->codejercicio;

      // Comprobamos existencia y estado del ejercicio
      if (!$eje0) {
         $this->new_error_msg("Ejercicio no encontrado.");
         return;
      }
      
      if (!$eje0->abierto()) {
         $this->new_error_msg("El ejercicio está cerrado.");
         return;
      }

      // Recogemos campos específicos
      switch ($model) {
         case 'albaran_proveedor': {
            $documento->codproveedor = $origen->codproveedor;
            $documento->nombre = $origen->nombre;
            $documento->numproveedor = $origen->numproveedor;
            $trazabilidad_page = 'compras_trazabilidad';
            break;
         }

         case 'pedido_cliente':
         case 'albaran_cliente': {
            $documento->codcliente = $origen->codcliente;
            $documento->nombrecliente = $origen->nombrecliente;
            $documento->numero2 = $origen->numero2;
            $documento->apartado = $origen->apartado;
            $documento->ciudad = $origen->ciudad;
            $documento->coddir = $origen->coddir;
            $documento->codpais = $origen->codpais;
            $documento->codpostal = $origen->codpostal;
            $documento->direccion = $origen->direccion;
            $documento->provincia = $origen->provincia;
            $documento->porcomision = $origen->porcomision;

            $documento->envio_nombre = $origen->envio_nombre;
            $documento->envio_apellidos = $origen->envio_apellidos;
            $documento->envio_codtrans = $origen->envio_codtrans;
            $documento->envio_codigo = $origen->envio_codigo;
            $documento->envio_codpais = $origen->envio_codpais;
            $documento->envio_provincia = $origen->envio_provincia;
            $documento->envio_ciudad = $origen->envio_ciudad;
            $documento->envio_codpostal = $origen->envio_codpostal;
            $documento->envio_direccion = $origen->envio_direccion;
            $documento->envio_apartado = $origen->envio_apartado;
            $trazabilidad_page = 'ventas_trazabilidad';
            break;
         }
         
         default: {
            $this->new_error_msg("¡Falta informar el tipo de documento a crear! ");
            return;
         }
      }
      
      // Recogemos campos comunes
      $documento->cifnif = $origen->cifnif;
      $documento->codalmacen = $origen->codalmacen;
      $documento->coddivisa = $origen->coddivisa;
      $documento->tasaconv = $origen->tasaconv;
      $documento->codpago = $origen->codpago;
      $documento->codserie = $origen->codserie;
      $documento->observaciones = $origen->observaciones;
      $documento->neto = $origen->neto;
      $documento->total = $origen->total;
      $documento->totaliva = $origen->totaliva;
      $documento->irpf = $origen->irpf;
      $documento->totalirpf = $origen->totalirpf;
      $documento->totalrecargo = $origen->totalrecargo;

      $documento->codagente = is_null($origen->codagente) ? $this->user->codagente : $origen->codagente;
      
      // Grabamos el nuevo documento
      if (!$documento->save()) {
         $this->new_error_msg("¡Imposible guardar el " . FS_ALBARAN . "!");
         return;
      }
         
      // Copiamos las lineas del documento
      $trazabilidad = FALSE;
      if ($this->generar_lineas_documento($documento, $origen, 'linea_'.$model, $trazabilidad)) {
         // copiamos o establecemos valores solicitados del documento origen
         foreach ($grabar_origen as $key => $value) {
            if (isset($value))
               $origen->$key = $value;
            else
               $origen->$key = $documento->$key;
         }

         // Grabamos cabecera nuevo documento
         if ($origen->save()) {
            $this->new_message("<a href='" . $documento->url() . "'>Documento</a> generado correctamente.");
            if ($trazabilidad)
               header('Location: index.php?page='.$trazabilidad_page.'&doc=albaran&id=' . $documento->idalbaran);
            else
               if (isset($_POST['facturar']))
                  header('Location: ' . $documento->url() . '&facturar=' . $_POST['facturar'] . '&petid=' . $this->random_string());
         } 
         else {
            $this->new_error_msg("¡Imposible vincular el documento origen con el nuevo documento!");
            $this->delete_documento($documento);
         }
      }
      else
         $this->delete_documento($documento);
   }
   
   private function eliminar_lineas_borradas($lineas, $numlineas) {
      foreach ($lineas as $lin) {
         for ($num = 0; $num <= $numlineas; $num++) {
            if (isset($_POST['idlinea_' . $num])) {
               if ($lin->idlinea == intval($_POST['idlinea_' . $num]))
                  break;
            }
         }

         if ($num > $numlineas) {
            if (!$lin->delete())
               $this->new_error_msg("¡Imposible eliminar la línea del artículo " . $lin->referencia . "!");
         }
      }      
   }
   
   private function calcular_impuesto_linea($linea, $serie, $regimeniva, $indice) {
      $linea->codimpuesto = NULL;
      $linea->iva = 0;
      $linea->recargo = 0;

      if (!$serie->siniva AND $regimeniva != 'Exento') {
         $imp0 = $this->impuesto->get_by_iva($_POST['iva_' . $indice]);
         if ($imp0)
            $linea->codimpuesto = $imp0->codimpuesto;

         $linea->iva = floatval($_POST['iva_' . $indice]);
         $linea->recargo = floatval($_POST['recargo_' . $indice]);
      }      
   }
   
   private function actualizar_linea_documento($documento, $linea, $valores, $indice) {
      $linea->cantidad = floatval($_POST['cantidad_' . $indice]);
      $linea->pvpunitario = floatval($_POST['pvp_' . $indice]);
      $linea->dtopor = floatval($_POST['dto_' . $indice]);
      $linea->pvpsindto = ($valores->cantidad * $valores->pvpunitario);
      $linea->pvptotal = ($valores->cantidad * $valores->pvpunitario * (100 - $valores->dtopor) / 100);
      $linea->descripcion = $_POST['desc_' . $indice];
      $linea->irpf = floatval($_POST['irpf_' . $indice]);

      if ($linea->save())
         $this->acumular_linea_documento($documento, $valores);
      else
         $this->new_error_msg("¡Imposible modificar la línea del artículo " . $valores->referencia . "!");      
   }
   
   private function acumular_linea_documento($documento, $linea) {
      $documento->neto += $linea->pvptotal;
      $documento->totaliva += ($linea->pvptotal * $linea->iva) / 100;
      $documento->totalirpf += ($linea->pvptotal * $linea->irpf) / 100;
      $documento->totalrecargo += ($linea->pvptotal * $linea->recargo) / 100;

      if ($linea->irpf > $documento->irpf)
         $documento->irpf = $linea->irpf;
   }
   
   private function redondear_importes_documento($documento) {
      $documento->neto = round($documento->neto, FS_NF0);
      $documento->totaliva = round($documento->totaliva, FS_NF0);
      $documento->totalirpf = round($documento->totalirpf, FS_NF0);
      $documento->totalrecargo = round($documento->totalrecargo, FS_NF0);
      $documento->total = $documento->neto + $documento->totaliva - $documento->totalirpf + $documento->totalrecargo;

      if (abs(floatval($_POST['atotal']) - $documento->total) >= .02) {
         $this->new_error_msg("El total difiere entre el controlador y la vista (" . $documento->total .
                 " frente a " . $_POST['atotal'] . "). Debes informar del error.");
      }      
   }
      
   private function get_historico_documento($historico, $ignorar_modelo, $campo_id, $valor_id, &$contador, $modelo = "presupuesto_cliente") {
      $sender = new $modelo();
      $tabla = $sender->table_name();
      
      switch ($tabla) {
         case "presupuestoscli": {
            if (!isset($campo_id))
               $campo_id = "idpresupuesto";
            $texto = FS_PRESUPUESTO;
            $sig_modelo = "pedido_cliente";
            $sig_id = "idpedido";
            break;
         }

         case "pedidoscli": {
            if (!isset($campo_id))
               $campo_id = "idpedido";
            $texto = FS_PEDIDO;
            $sig_modelo = "albaran_cliente";
            $sig_id = "idalbaran";
            break;
         }
         
         case "albaranescli": {
            if (!isset($campo_id))
               $campo_id = "idalbaran";
            $texto = FS_ALBARAN;
            $sig_modelo = "factura_cliente";
            $sig_id = "idfactura";
            break;
         }
         
         case "facturascli": {
            if (!isset($campo_id))
               $campo_id = "idfactura";
            $texto = FS_FACTURA;
            $sig_modelo = "";
            $sig_id = "";
            break;
         }

         default: {
            return;
         }
      }
      
      $sql = "SELECT *"
           .  " FROM " .$tabla
           . " WHERE " .$campo_id. " = " . strval($valor_id)
           . " ORDER BY " .$campo_id. " ASC;";

      $data = $this->db->select($sql);
      if ($data) {
         foreach ($data as $record) {
            $documento = new $modelo($record);
            if ($ignorar_modelo != $modelo) {
               $historico[] = array(
                   'orden' => $contador,
                   'documento' => $texto,
                   'modelo' => $documento
               );
               $contador++;
            }
            
            if ($sig_modelo) {
               $valor_id = $documento->$sig_id;
               if (isset($valor_id))
                  $this->get_historico_documento ($historico, $ignorar_modelo, NULL, $valor_id, $contador, $sig_modelo);
            }   
         }
      }
   }
   
   protected function private_core_shared($id, $id_new, &$url) {
      $this->almacen = new almacen();
      $this->divisa = new divisa();
      $this->ejercicio = new ejercicio();
      $this->fabricante = new fabricante();
      $this->familia = new familia();
      $this->forma_pago = new forma_pago();
      $this->impuesto = new impuesto();
      $this->serie = new serie();
      
      $this->ppage = $this->page->get($id);
      $this->agente = FALSE;
      $this->pedido = FALSE;
      
      // Comprobamos si el usuario tiene acceso a nuevo registro, necesario para poder añadir líneas.
      $url = FALSE;
      if ($this->user->have_access_to($id_new, FALSE)) {
         $nuevo = $this->page->get($id_new);
         if ($nuevo) {
            $url = $nuevo->url();
         }
      }
   }
   
   private function nueva_version_shared($document, $new_document) {
      if (isset($new_document->idpresupuesto))
         $new_document->idpresupuesto = NULL;

      if (isset($new_document->idalbaran))
         $new_document->idalbaran = NULL;

      if (isset($new_document->idpedido))
         $new_document->idpedido = NULL;
      
      if (isset($new_document->editable))
         $new_document->editable = TRUE;
               
      $new_document->fecha = $this->today();
      $new_document->hora = $this->hour();
      $new_document->numdocs = 0;

      if ($document->idoriginal)
         $new_document->idoriginal = $document->idoriginal;
      else {
         if (isset($new_document->idpresupuesto))
            $new_document->idoriginal = $document->idpresupuesto;

         if (isset($new_document->idpedido))
            $new_document->idoriginal = $document->idpedido;
      }

      /// enlazamos con el ejercicio correcto
      $ejercicio = $this->ejercicio->get_by_fecha($new_document->fecha);
      if ($ejercicio)
         $new_document->codejercicio = $ejercicio->codejercicio;

      if ($new_document->save()) {
         /// también copiamos las líneas
         foreach ($document->get_lineas() as $linea) {
            $newl = clone $linea;
            $newl->idlinea = NULL;

            // solo guardamos enlace en los presupuestos/pedidos compra/venta
            if (isset($newl->idpresupuesto))
               $newl->idpresupuesto = $new_document->idpresupuesto;
            
            if (isset($new_document->idpedido))
               $newl->idpedido = $new_document->idpedido;
            
            $newl->save();
         }

         $this->new_message('<a href="' . $new_document->url() . '">Documento</a> copiado correctamente.');
         header('Location: ' . $new_document->url() . '&nversionok=TRUE');
      } 
      else
         $this->new_error_msg('Error al copiar el documento.');
   }
   
   private function url_shared($document) {
      if (!isset($document))
         return parent::url();
      
      if ($document)
         return $document->url();
      
      return $this->page->url();
   }
   
   private function modificar_shared($documento, &$serie) {
      // Si el documento es editable
      $result = $documento->editable;
      if ($result) {
         // Calculamos el ejercicio
         $eje0 = $this->ejercicio->get_by_fecha($_POST['fecha'], FALSE);
         if ($eje0) {
            $documento->fecha = $_POST['fecha'];
            $documento->hora = $_POST['hora'];

            if (isset($_POST['fechasalida']))
               $documento->fechasalida = $_POST['fechasalida'] != '' ? $_POST['fechasalida'] : NULL;

            if (isset($_POST['finoferta']))
               $documento->finoferta = $_POST['finoferta'] != '' ? $_POST['finoferta'] : NULL;
                        
            if ($documento->codejercicio != $eje0->codejercicio) {
               $documento->codejercicio = $eje0->codejercicio;
               $documento->new_codigo();
            }
         } 
         else
            $this->new_error_msg('Ningún ejercicio encontrado.');
      
         $documento->codalmacen = $_POST['almacen'];
         $documento->codpago = $_POST['forma_pago'];         

         // Hay cambio de serie
         if ($_POST['serie'] != $documento->codserie) {
            $serie2 = $this->serie->get($_POST['serie']);
            if ($serie2) {
               $documento->codserie = $serie2->codserie;
               $documento->new_codigo();

               $serie = $serie2;
            }
         }
         
         // Hay cambio de divisa
         if ($_POST['divisa'] != $documento->coddivisa) {
            $divisa = $this->divisa->get($_POST['divisa']);
            if ($divisa) {
               $documento->coddivisa = $divisa->coddivisa;
               $documento->tasaconv = $divisa->tasaconv;
            }
         }
         else {
            if ($_POST['tasaconv'] != '')
               $documento->tasaconv = floatval($_POST['tasaconv']);
         }
         
         if (isset($_POST['numlineas'])) {
            $documento->neto = 0;
            $documento->totaliva = 0;
            $documento->totalirpf = 0;
            $documento->totalrecargo = 0;
            $documento->irpf = 0;
         }
      }
      return $result;
   }
   
   private function cambiar_cliente_shared($documento) {
      $cliente = $this->cliente->get($_POST['cliente']);
      if ($cliente) {
         $documento->codcliente = $cliente->codcliente;
         $documento->cifnif = $cliente->cifnif;
         $documento->nombrecliente = $cliente->razonsocial;
         $documento->apartado = NULL;
         $documento->ciudad = NULL;
         $documento->coddir = NULL;
         $documento->codpais = NULL;
         $documento->codpostal = NULL;
         $documento->direccion = NULL;
         $documento->provincia = NULL;

         foreach ($cliente->get_direcciones() as $d) {
            if ($d->domfacturacion) {
               $documento->apartado = $d->apartado;
               $documento->ciudad = $d->ciudad;
               $documento->coddir = $d->id;
               $documento->codpais = $d->codpais;
               $documento->codpostal = $d->codpostal;
               $documento->direccion = $d->direccion;
               $documento->provincia = $d->provincia;
               break;
            }
         }
      }
      else {
         $documento->codcliente = NULL;
         $documento->nombrecliente = $_POST['nombrecliente'];
         $documento->cifnif = $_POST['cifnif'];
         $documento->coddir = NULL;
      }      
   }
   
   private function update_desde_params($documento) {
      $documento->nombrecliente = $_POST['nombrecliente'];
      $documento->cifnif = $_POST['cifnif'];
      $documento->codpais = $_POST['codpais'];
      $documento->provincia = $_POST['provincia'];
      $documento->ciudad = $_POST['ciudad'];
      $documento->codpostal = $_POST['codpostal'];
      $documento->direccion = $_POST['direccion'];
      $documento->apartado = $_POST['apartado'];

      $documento->envio_nombre = $_POST['envio_nombre'];
      $documento->envio_apellidos = $_POST['envio_apellidos'];            
      $documento->envio_codigo = $_POST['envio_codigo'];
      $documento->envio_codpais = $_POST['envio_codpais'];
      $documento->envio_provincia = $_POST['envio_provincia'];
      $documento->envio_ciudad = $_POST['envio_ciudad'];
      $documento->envio_codpostal = $_POST['envio_codpostal'];
      $documento->envio_direccion = $_POST['envio_direccion'];
      $documento->envio_apartado = $_POST['envio_apartado'];

      $documento->envio_codtrans = $_POST['envio_codtrans'] != '' ? $_POST['envio_codtrans'] : NULL;            
   }

   private function get_lineas_stock_shared($documento, $campo_id) {
      $lineas = array();
      $valor_id = $documento->$campo_id;
      $sql = "SELECT l.referencia,l.descripcion,l.cantidad,s.cantidad as stock,s.ubicacion FROM lineas" .$documento->table_name()." l"
              . " LEFT JOIN stocks s ON l.referencia = s.referencia"
              . " AND s.codalmacen = " . $documento->var2str($documento->codalmacen)
              . " WHERE l.".$campo_id. " = " . $documento->var2str($valor_id)
              . " ORDER BY referencia ASC;";
      $data = $this->db->select($sql);
      if ($data) {
         $art0 = new articulo();

         foreach ($data as $d) {
            $articulo = $art0->get($d['referencia']);
            if ($articulo) {
               $lineas[] = array(
                   'articulo' => $articulo,
                   'descripcion' => $d['descripcion'],
                   'cantidad' => floatval($d['cantidad']),
                   'stock' => floatval($d['stock']),
                   'ubicacion' => $d['ubicacion']
               );
            }
         }
      }

      return $lineas;
   }   
}