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
   
   /**
    * Busca un documento del tipo indicado en $modelo por su identificador
    * o por el parámetro GET 'id' y lo enlaza a $documento
    * @param string $modelo
    * @param string $campo_id
    * @param model  $documento
    * @return boolean
    */
   private function get_documento($modelo, $campo_id, &$documento) {
      $valor_id = filter_input(INPUT_POST, $campo_id);
      if (isset($valor_id)) {
         $documento = $modelo->get($valor_id);
         $this->modificar();
         return TRUE;
      } 

      $id = filter_input(INPUT_GET, 'id');
      if (isset($id)) {
         $documento = $modelo->get($id);
         return TRUE;
      }
      
      return FALSE;
   }
   
   /**
    * Elimina el documento indicado
    * @param model $documento
    */
   private function delete_documento($documento) {
      if ($documento->delete())
         $message = "El documento se ha borrado.";
      else
         $message = "¡Imposible borrar el documento!"; 
      
      $this->new_error_msg($message);
   }
   
   /**
    * Crear las líneas de un documento en base a las líneas de otro documento
    * @param model  $documento            (al que se añaden las lineas)
    * @param model  $origen               (de donde se leen las lineas)
    * @param string $linea_model
    * @param boolean $trazabilidad
    * @return boolean
    */
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
               $costemedio = (null !== filter_input(INPUT_POST, 'costemedio'));
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
            
            $stock = filter_input(INPUT_POST, 'stock');
            if ($linea->referencia AND isset($stock)) {
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
      
   /**
    * Crea un documento en base a un documento origen.
    * Graba los campos indicados en el origen.
    * @param string $model
    * @param string $fecha
    * @param model  $origen
    * @param array  $grabar_origen                [campo => valor]
    */
   private function generar_documento($model, $fecha, $origen, $grabar_origen) {
      $documento = new $model;
      
      // Calculamos la fecha del documento
      $date = filter_input(INPUT_POST, $fecha);
      if (isset($date))
         $documento->fecha = $date;
      
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
         $this->new_error_msg("¡Imposible guardar el documento!");
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
            else {
               $facturar = filter_input(INPUT_POST, 'facturar');
               if (isset($facturar))
                  header('Location: ' . $documento->url() . '&facturar=' . $facturar . '&petid=' . $this->random_string());
            }
         } 
         else {
            $this->new_error_msg("¡Imposible vincular el documento origen con el nuevo documento!");
            $this->delete_documento($documento);
         }
      }
      else
         $this->delete_documento($documento);
   }
   
   /**
    * Elimina en la base de datos las lineas borradas en el controllador
    * @param array   $lineas
    * @param integer $numlineas
    */
   private function eliminar_lineas_borradas($lineas, $numlineas) {
      foreach ($lineas as $lin) {
         for ($num = 0; $num <= $numlineas; $num++) {
            $idlinea = filter_input(INPUT_POST, 'idlinea_' .$num);
            if (isset($idlinea)) {
               if ($lin->idlinea == intval($idlinea))
                  break;
            }
         }

         if ($num > $numlineas) {
            if (!$lin->delete())
               $this->new_error_msg("¡Imposible eliminar la línea del artículo " . $lin->referencia . "!");
         }
      }      
   }
   
   /**
    * Calcula los campos de impuesto de una linea de documento
    * @param model   $linea
    * @param string  $serie
    * @param string  $regimeniva
    * @param integer $indice
    */
   private function calcular_impuesto_linea($linea, $serie, $regimeniva, $indice) {
      $linea->codimpuesto = NULL;
      $linea->iva = 0;
      $linea->recargo = 0;

      if (!$serie->siniva AND $regimeniva != 'Exento') {
         $pct_iva = filter_input(INPUT_POST, 'iva_'.$indice);
         $imp0 = $this->impuesto->get_by_iva($pct_iva);
         if ($imp0)
            $linea->codimpuesto = $imp0->codimpuesto;

         $linea->iva = floatval($pct_iva);
         $linea->recargo = floatval(filter_input(INPUT_POST, 'recargo_' . $indice));
      }      
   }
   
   /**
    * Actualiza la linea de documento en base a los parametros POST
    * y la lista de valores indicados
    * @param model   $documento
    * @param model   $linea
    * @param object  $valores
    * @param integer $indice
    */
   private function actualizar_linea_documento($documento, $linea, $valores, $indice) {
      $linea->cantidad = floatval(filter_input(INPUT_POST, 'cantidad_' . $indice));
      $linea->pvpunitario = floatval(filter_input(INPUT_POST, 'pvp_' . $indice));
      $linea->dtopor = floatval(filter_input(INPUT_POST, 'dto_' . $indice));
      $linea->pvpsindto = ($valores->cantidad * $valores->pvpunitario);
      $linea->pvptotal = ($valores->cantidad * $valores->pvpunitario * (100 - $valores->dtopor) / 100);
      $linea->descripcion = filter_input(INPUT_POST, 'desc_' . $indice);
      $linea->irpf = floatval(filter_input(INPUT_POST, 'irpf_' . $indice));

      if ($linea->save())
         $this->acumular_linea_documento($documento, $valores);
      else
         $this->new_error_msg("¡Imposible modificar la línea del artículo " . $valores->referencia . "!");      
   }
   
   /**
    * Acumula en la cabecera de documento los importes de la linea indicada
    * @param model $documento
    * @param model $linea
    */
   private function acumular_linea_documento($documento, $linea) {
      $documento->neto += $linea->pvptotal;
      $documento->totaliva += ($linea->pvptotal * $linea->iva) / 100;
      $documento->totalirpf += ($linea->pvptotal * $linea->irpf) / 100;
      $documento->totalrecargo += ($linea->pvptotal * $linea->recargo) / 100;

      if ($linea->irpf > $documento->irpf)
         $documento->irpf = $linea->irpf;
   }
   
   /**
    * Redondea los importes del documento
    * @param model $documento
    */
   private function redondear_importes_documento($documento) {
      $documento->neto = round($documento->neto, FS_NF0);
      $documento->totaliva = round($documento->totaliva, FS_NF0);
      $documento->totalirpf = round($documento->totalirpf, FS_NF0);
      $documento->totalrecargo = round($documento->totalrecargo, FS_NF0);
      $documento->total = $documento->neto + $documento->totaliva - $documento->totalirpf + $documento->totalrecargo;

      $atotal = filter_input(INPUT_POST, 'atotal');
      if (abs(floatval($atotal) - $documento->total) >= .02) {
         $this->new_error_msg("El total difiere entre el controlador y la vista (" . $documento->total .
                 " frente a " . $atotal . "). Debes informar del error.");
      }      
   }
   
   /**
    * Añade al parametro $historico la lista de documentos 
    * dependientes del documento origen.
    * @param array  $historico
    * @param string  $ignorar_modelo
    * @param string  $campo_id
    * @param integer $valor_id
    * @param integer $contador
    * @param string  $modelo
    */
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
   
   /**
    * Actualiza los campos del documento en base a los parametros POST
    * @param model $documento
    */
   private function update_desde_params($documento) {
      $documento->nombrecliente = filter_input(INPUT_POST, 'nombrecliente');
      $documento->cifnif = filter_input(INPUT_POST, 'cifnif');
      $documento->codpais = filter_input(INPUT_POST, 'codpais');
      $documento->provincia = filter_input(INPUT_POST, 'provincia');
      $documento->ciudad = filter_input(INPUT_POST, 'ciudad');
      $documento->codpostal = filter_input(INPUT_POST, 'codpostal');
      $documento->direccion = filter_input(INPUT_POST, 'direccion');
      $documento->apartado = filter_input(INPUT_POST, 'apartado');

      $documento->envio_nombre = filter_input(INPUT_POST, 'envio_nombre');
      $documento->envio_apellidos = filter_input(INPUT_POST, 'envio_apellidos');
      $documento->envio_codigo = filter_input(INPUT_POST, 'envio_codigo');
      $documento->envio_codpais = filter_input(INPUT_POST, 'envio_codpais');
      $documento->envio_provincia = filter_input(INPUT_POST, 'envio_provincia');
      $documento->envio_ciudad = filter_input(INPUT_POST, 'envio_ciudad');
      $documento->envio_codpostal = filter_input(INPUT_POST, 'envio_codpostal');
      $documento->envio_direccion = filter_input(INPUT_POST, 'envio_direccion');
      $documento->envio_apartado = filter_input(INPUT_POST, 'envio_apartado');

      $envio = filter_input(INPUT_POST, 'envio_codtrans');
      $documento->envio_codtrans = ($envio != '') ? $envio : NULL;            
   }
   
   /**
    * Código unificado del método "private_core" 
    * en documentos de presupuestos y pedidos
    * @param string $id
    * @param string $id_new
    * @param string $url
    */
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
   
   /**
    * Código unificado del método "nueva_version" 
    * en documentos de presupuestos y pedidos
    * @param model $document
    * @param model $new_document
    */
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
   
   /**
    * Código unificado del método "url" 
    * en documentos de presupuestos y pedidos
    * @param model $document
    * @return string
    */
   private function url_shared($document) {
      if (!isset($document))
         return parent::url();
      
      if ($document)
         return $document->url();
      
      return $this->page->url();
   }
   
   /**
    * Código unificado del método "modificar" 
    * en documentos de presupuestos y pedidos
    * @param model $documento
    * @param string $serie
    * @return boolean
    */
   private function modificar_shared($documento, &$serie) {
      // Si el documento es editable
      $result = $documento->editable;
      if ($result) {
         // Calculamos el ejercicio
         $fecha = filter_input(INPUT_POST, 'fecha');
         $eje0 = $this->ejercicio->get_by_fecha($fecha, FALSE);
         if ($eje0) {
            $documento->fecha = $fecha;
            $documento->hora = filter_input(INPUT_POST, 'hora');

            $fechasalida = filter_input(INPUT_POST, 'fechasalida');
            if (isset($fechasalida))
               $documento->fechasalida = ($fechasalida != '') ? $fechasalida : NULL;

            $finoferta = filter_input(INPUT_POST, 'finoferta');
            if (isset($finoferta))
               $documento->finoferta = ($finoferta != '') ? $finoferta : NULL;
                        
            if ($documento->codejercicio != $eje0->codejercicio) {
               $documento->codejercicio = $eje0->codejercicio;
               $documento->new_codigo();
            }
         } 
         else
            $this->new_error_msg('Ningún ejercicio encontrado.');
      
         $documento->codalmacen = filter_input(INPUT_POST, 'almacen');
         $documento->codpago = filter_input(INPUT_POST, 'forma_pago');

         // Hay cambio de serie
         $nueva_serie = filter_input(INPUT_POST, 'serie');
         if ($nueva_serie != $documento->codserie) {
            $serie2 = $this->serie->get($nueva_serie);
            if ($serie2) {
               $documento->codserie = $serie2->codserie;
               $documento->new_codigo();

               $serie = $serie2;
            }
         }
         
         // Hay cambio de divisa
         $coddivisa = filter_input(INPUT_POST, 'divisa');
         if ($coddivisa != $documento->coddivisa) {
            $divisa = $this->divisa->get($coddivisa);
            if ($divisa) {
               $documento->coddivisa = $divisa->coddivisa;
               $documento->tasaconv = $divisa->tasaconv;
            }
         }
         else {
            $tasaconv = filter_input(INPUT_POST, 'tasaconv');
            if ($tasaconv != '')
               $documento->tasaconv = floatval($tasaconv);
         }
         
         if (null !== filter_input(INPUT_POST, 'numlineas')) {
            $documento->neto = 0;
            $documento->totaliva = 0;
            $documento->totalirpf = 0;
            $documento->totalrecargo = 0;
            $documento->irpf = 0;
         }
      }
      return $result;
   }
   
   /**
    * Código unificado del método "cambiar_cliente" 
    * en documentos de presupuestos y pedidos
    * @param model $documento
    */
   private function cambiar_cliente_shared($documento) {
      $codcliente = filter_input(INPUT_POST, 'cliente');
      $cliente = $this->cliente->get($codcliente);
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
         $documento->nombrecliente = filter_input(INPUT_POST, 'nombrecliente');
         $documento->cifnif = filter_input(INPUT_POST, 'cifnif');
         $documento->coddir = NULL;
      }      
   }

   /**
    * Código unificado del método "get_lineas_stock" 
    * en documentos de presupuestos y pedidos
    * @param model  $documento
    * @param string $campo_id
    * @return array
    */
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