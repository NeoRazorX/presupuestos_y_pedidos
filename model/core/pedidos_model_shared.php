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

namespace FacturaScripts\model;

/**
 * Procedures and shared utilities
 * 
 * @author Artex Trading sa (2017) <jcuello@artextrading.com>
 */
trait pedidos_model_shared {
   abstract function no_html($t);
   abstract function floatcmp($f1, $f2, $precision = 10, $round = FALSE);
   abstract function new_error_msg($msg = FALSE);
     
   /**
    * Código único. Para humanos.
    * @var string 
    */
   public $codigo;

   /**
    * Serie relacionada.
    * @var string 
    */
   public $codserie;
   
   /**
    * Ejercicio relacionado. El que corresponde a la fecha.
    * @var string 
    */
   public $codejercicio;
   
   public $fecha;
   public $hora;   
   public $cifnif;

   /**
    * Empleado que ha creado el pedido.
    * @var string 
    */
   public $codagente;

   /**
    * Forma de pago del pedido.
    * @var string 
    */
   public $codpago;

   /**
    * Divisa del pedido.
    * @var string 
    */
   public $coddivisa;   
   
   /**
    * Almacén donde se realiza el movimiento de stock.
    * @var string 
    */
   public $codalmacen;

   /**
    * Número de identificación del documento.
    * Único para la serie+ejercicio.
    * @var integer 
    */
   public $numero;
   
   /**
    * Número de documentos adjuntos.
    * @var integer 
    */
   public $numdocs;

   /**
    * Si este documento es la versión de otro, aquí se almacena el documento del original.
    * @var integer 
    */
   public $idoriginal;

   /**
    * Observaciones en la cabecera de documento.
    * @var string 
    */
   public $observaciones;

   /**
    * Importe total antes de impuestos.
    * Es la suma del pvptotal de las líneas.
    * @var float 
    */
   public $neto;

   /**
    * Importe total de la factura, con impuestos.
    * @var float 
    */
   public $total;

   /**
    * Suma del IVA de las líneas.
    * @var float 
    */
   public $totaliva;
   
   /**
    * Total expresado en euros, por si no fuese la divisa del pedido.
    * totaleuros = total/tasaconv
    * No hace falta rellenarlo, al hacer save() se calcula el valor.
    * @var float 
    */
   public $totaleuros;

   /**
    * % de retención IRPF del pedido. Se obtiene de la serie.
    * Cada línea puede tener un % distinto.
    * @var float 
    */
   public $irpf;
   
   /**
    * Suma de las retenciones IRPF de las líneas del pedido.
    * @var float 
    */
   public $totalirpf;

   /**
    * Suma total del recargo de equivalencia de las líneas.
    * @var float 
    */
   public $totalrecargo;
   
   /**
    * Tasa de conversión a Euros de la divisa seleccionada.
    * @var float 
    */
   public $tasaconv;


   /**
    * Devuelve la fecha del documento formateada a:
    * con segundos 'H:i:s'
    * sin segundos 'H:i'
    * @param boolean $segundos
    * @return date
    */
   private function show_hora($segundos = TRUE) {
      return $segundos 
                ? Date('H:i:s', strtotime($this->fecha)) 
                : Date('H:i', strtotime($this->fecha));
   }

   /**
    * Devuelve las observaciones del documento con el formato:
    * vacio: '-'
    * hasta 60 digitos.
    * @return string
    */
   public function observaciones_resume() {
      switch (strlen($this->observaciones)) {
         case 0: {
            $result = '-';
            break;
         }
         
         case strlen($this->observaciones) > 59: {
            $result = substr($this->observaciones, 0, 50) . '...';
            break;
         }

         default: {
            $result = $this->observaciones;
            break;
         }
      }
      return $result;
   }   
   
   /**
    * Código unificado del método "clear" 
    * en documentos de presupuestos y pedidos
    * @param boolean $es_venta
    */
   private function clear_shared($es_venta = FALSE) {
      $this->idpedido = NULL;
      $this->codigo = NULL;
      $this->codagente = NULL;
      $this->codpago = $this->default_items->codpago();
      $this->codserie = $this->default_items->codserie();
      $this->codejercicio = NULL;
      $this->coddivisa = NULL;
      $this->codalmacen = $this->default_items->codalmacen();
      $this->numero = NULL;
      $this->cifnif = '';
      $this->fecha = Date('d-m-Y');
      $this->hora = Date('H:i:s');

      $this->tasaconv = 1;
      $this->neto = 0;
      $this->total = 0;
      $this->totaliva = 0;
      $this->totaleuros = 0;
      $this->irpf = 0;
      $this->totalirpf = 0;
      $this->totalrecargo = 0;
      $this->observaciones = NULL;
      $this->editable = TRUE;
      $this->numdocs = 0;
      $this->idoriginal = NULL;      
      
      if ($es_venta) {
         $this->codcliente = NULL;
         $this->codpais = NULL;
         $this->coddir = NULL;
         $this->codpostal = '';
         $this->numero2 = NULL;
         $this->nombrecliente = '';
         $this->direccion = NULL;
         $this->ciudad = NULL;
         $this->provincia = NULL;
         $this->apartado = NULL;
         $this->porcomision = 0;
         $this->status = 0;
         $this->femail = NULL;
         
         $this->envio_codtrans = NULL;
         $this->envio_codigo = NULL;
         $this->envio_nombre = NULL;
         $this->envio_apellidos = NULL;
         $this->envio_apartado = NULL;
         $this->envio_direccion = NULL;
         $this->envio_codpostal = NULL;
         $this->envio_ciudad = NULL;
         $this->envio_provincia = NULL;
         $this->envio_codpais = NULL;
         
      }
   }
   
   /**
    * Código unificado del método "load_from_data" 
    * en documentos de presupuestos y pedidos
    * @param array $data
    * @param boolean $es_venta
    */
   private function load_from_data_shared($data, $es_venta = FALSE) {
      $this->idpedido = $this->intval($data['idpedido']);
      $this->codigo = $data['codigo'];
      $this->numero = $data['numero'];
      $this->fecha = Date('d-m-Y', strtotime($data['fecha']));
      $this->hora = Date('H:i:s', strtotime($data['hora']));      
      $this->codagente = $data['codagente'];
      $this->codpago = $data['codpago'];
      $this->codserie = $data['codserie'];
      $this->codejercicio = $data['codejercicio'];
      $this->coddivisa = $data['coddivisa'];
      $this->codalmacen = $data['codalmacen'];
      $this->cifnif = $data['cifnif'];

      $this->neto = floatval($data['neto']);
      $this->total = floatval($data['total']);
      $this->totaliva = floatval($data['totaliva']);
      $this->totaleuros = floatval($data['totaleuros']);
      $this->irpf = floatval($data['irpf']);
      $this->totalirpf = floatval($data['totalirpf']);
      $this->tasaconv = floatval($data['tasaconv']);
      $this->totalrecargo = floatval($data['totalrecargo']);
      $this->observaciones = $data['observaciones'];
      
      $this->numdocs = intval($data['numdocs']);
      $this->idoriginal = $this->intval($data['idoriginal']);
      
      if ($es_venta) {
         $this->codcliente = $data['codcliente'];
         $this->nombrecliente = $data['nombrecliente'];
         $this->direccion = $data['direccion'];
         $this->ciudad = $data['ciudad'];
         $this->provincia = $data['provincia'];
         $this->apartado = $data['apartado'];
         $this->codpais = $data['codpais'];
         $this->coddir = $data['coddir'];
         $this->codpostal = $data['codpostal'];
         $this->porcomision = floatval($data['porcomision']);
         $this->femail = is_null($data['femail']) ? NULL : Date('d-m-Y', strtotime($data['femail']));
         $this->numero2 = $data['numero2'];
         
         $this->envio_codtrans = $data['codtrans'];
         $this->envio_codigo = $data['codigoenv'];
         $this->envio_nombre = $data['nombreenv'];
         $this->envio_apellidos = $data['apellidosenv'];
         $this->envio_apartado = $data['apartadoenv'];
         $this->envio_direccion = $data['direccionenv'];
         $this->envio_codpostal = $data['codpostalenv'];
         $this->envio_ciudad = $data['ciudadenv'];
         $this->envio_provincia = $data['provinciaenv'];
         $this->envio_codpais = $data['codpaisenv'];         
      }
   }

   /**
    * Código unificado del método "get" 
    * en documentos de presupuestos y pedidos
    * @param string  $modelo
    * @param string  $campo_id
    * @param integer $valor_id
    * @return boolean
    */
   private function get_shared($modelo, $campo_id, $valor_id) {
      $documento = $this->db->select("SELECT * FROM " . $this->table_name() . " WHERE " .$campo_id." = " . strval($valor_id) . ";");
      $result = FALSE;
      if ($documento)
         $result = new $modelo($documento[0]);
      return $result;
   }
   
   /**
    * Código unificado del método "get_versiones" 
    * en documentos de presupuestos y pedidos
    * @param string $modelo
    * @param string $campo_id
    * @param integer $valor_id
    * @return array
    */
   private function get_versiones_shared($modelo, $campo_id, $valor_id) {
      $result = array();

      $sql = "SELECT * FROM " . $this->table_name() . " WHERE idoriginal = " . $this->var2str($valor_id);
      if ($this->idoriginal) {
         $sql .= " OR idoriginal = " . strval($this->idoriginal);
         $sql .= " OR ".$campo_id ." = " . strval($this->idoriginal);
      }
      $sql .= "ORDER BY fecha DESC, hora DESC;";

      $data = $this->db->select($sql);
      if ($data)
         foreach ($data as $record) {
            $result[] = new $modelo($record);
         }

      return $result;
   }

   /**
    * Código unificado del método "new_codigo" 
    * en documentos de presupuestos y pedidos
    * @param string $id_secuencia
    * @param string $texto
    */
   private function new_codigo_shared($id_secuencia, $texto) {
      $sec0 = new \secuencia();
      $sec = $sec0->get_by_params2($this->codejercicio, $this->codserie, $id_secuencia);
      if ($sec) {
         $this->numero = $sec->valorout;
         $sec->valorout++;
         $sec->save();
      }

      if (!$sec OR $this->numero <= 1) {
         $sql = "SELECT MAX(" . $this->db->sql_to_int('numero') . ") as num"
               . " FROM " . $this->table_name() 
               ." WHERE codejercicio = '" . strval($this->codejercicio) . "'"
               .  " AND codserie = '" . strval($this->codserie) . "';";
         $numero = $this->db->select($sql);
         if ($numero)
            $this->numero = 1 + intval($numero[0]['num']);
         else
            $this->numero = 1;

         if ($sec) {
            $sec->valorout = 1 + $this->numero;
            $sec->save();
         }
      }

      if (FS_NEW_CODIGO == 'eneboo')
         $this->codigo = $this->codejercicio . sprintf('%02s', $this->codserie) . sprintf('%06s', $this->numero);
      else
         $this->codigo = strtoupper(substr($texto, 0, 3)) . $this->codejercicio . $this->codserie . $this->numero . 'C';
   }
   
   /**
    * Comprueba los datos del documento, devuelve TRUE si está todo correcto
    * @param boolean $es_venta
    * @return boolean
    */
   private function test_shared($es_venta = FALSE) {
      $this->observaciones = $this->no_html($this->observaciones);

      if ($es_venta) {
         $this->numero2 = $this->no_html($this->numero2);
         $this->nombrecliente = ($this->nombrecliente != '') ? $this->no_html($this->nombrecliente) : '-'; 
         $this->direccion = $this->no_html($this->direccion);
         $this->ciudad = $this->no_html($this->ciudad);
         $this->provincia = $this->no_html($this->provincia);

         $this->envio_nombre = $this->no_html($this->envio_nombre);
         $this->envio_apellidos = $this->no_html($this->envio_apellidos);
         $this->envio_direccion = $this->no_html($this->envio_direccion);
         $this->envio_ciudad = $this->no_html($this->envio_ciudad);
         $this->envio_provincia = $this->no_html($this->envio_provincia);
      }
      
      /**
       * Usamos el euro como divisa puente a la hora de sumar, comparar
       * o convertir cantidades en varias divisas. Por este motivo necesimos
       * muchos decimales.
       */
      $this->totaleuros = round($this->total / $this->tasaconv, 5);
      $total = $this->neto + $this->totaliva + $this->totalrecargo - $this->totalirpf;
      $result = $this->floatcmp($this->total, $total, FS_NF0, TRUE);
      if (!$result)
         $this->new_error_msg("Error grave: El total está mal calculado. ¡Informa del error!");
      
      return $result;
   }

   /**
    * Código unificado del método "full_test" 
    * en documentos de presupuestos y pedidos
    * @param model $lineas
    * @param string $texto
    * @return boolean
    */
   private function full_test_shared($lineas, $texto) {
      /// comprobamos las líneas
      $neto = 0;
      $iva = 0;
      $irpf = 0;
      $recargo = 0;
      foreach ($lineas as $record) {
         $result = $record->test() AND $result;
         $neto += $record->pvptotal;
         $iva += $record->pvptotal * $record->iva / 100;
         $irpf += $record->pvptotal * $record->irpf / 100;
         $recargo += $record->pvptotal * $record->recargo / 100;
      }

      $tot_neto = round($neto, FS_NF0);
      $tot_iva = round($iva, FS_NF0);
      $tot_irpf = round($irpf, FS_NF0);
      $tot_recargo = round($recargo, FS_NF0);
      $total = $tot_neto + $tot_iva + $tot_recargo - $tot_irpf;
      
      if (!$this->floatcmp($this->neto, $tot_neto, FS_NF0, TRUE)) {
         $this->new_error_msg("Valor neto de " . $texto . " incorrecto. Valor correcto: " . $neto);
         return FALSE;
      }

      if (!$this->floatcmp($this->totaliva, $tot_iva, FS_NF0, TRUE)) {
         $this->new_error_msg("Valor totaliva de " . $texto . " incorrecto. Valor correcto: " . $iva);
         return FALSE;
      }
      
      if (!$this->floatcmp($this->totalirpf, $tot_irpf, FS_NF0, TRUE)) {
         $this->new_error_msg("Valor totalirpf de " . $texto . " incorrecto. Valor correcto: " . $irpf);
         return FALSE;
      }
      
      if (!$this->floatcmp($this->totalrecargo, $tot_recargo, FS_NF0, TRUE)) {
         $this->new_error_msg("Valor totalrecargo de " . $texto . " incorrecto. Valor correcto: " . $recargo);
         return FALSE;
      }
      
      if (!$this->floatcmp($this->total, $total, FS_NF0, TRUE)) {
         $this->new_error_msg("Valor total del " . $texto . " incorrecto. Valor correcto: " . $total);
         return FALSE;
      }

      return $result;
   }   

   /**
    * Código unificado del método "search" 
    * en documentos de presupuestos y pedidos
    * @param string $query
    * @param string $field_num
    * @return string
    */
   private function search_shared($query, $field_num) {
      $value = mb_strtolower($this->no_html($query), 'UTF8');

      $where = "";
      if (is_numeric($value)) {
         $where = "codigo LIKE '%" . $value . "%'"
            . " OR " .$field_num ." LIKE '%" . $value . "%'"
            . " OR observaciones LIKE '%" . $value . "%'"
            . " OR total BETWEEN '" . ($value - .01) . "' AND '" . ($value + .01) . "'";
      }
      else {
         if (preg_match('/^([0-9]{1,2})-([0-9]{1,2})-([0-9]{4})$/i', $value))
            $where = "fecha = " . $this->var2str($value) . " OR observaciones LIKE '%" . $value . "%'";
         else {
            $where = "lower(codigo) LIKE '%" . $value . "%'"
               . " OR lower(" .$field_num .") LIKE '%" . $value . "%'"
               . " OR lower(observaciones) LIKE '%" . str_replace(' ', '%', $value) . "%'";
         }
      }

      return $where;
   }   
   
   /**
    * Código unificado del método "all_desde" 
    * en documentos de presupuestos y pedidos
    * @param string $desde
    * @param string $hasta
    * @param string $codserie
    * @param string $codagente
    * @param string $forma_pago
    * @param string $almacen
    * @param string $divisa
    * @return string
    */
   private function all_desde_shared($desde, $hasta, $codserie, $codagente, $forma_pago, $almacen, $divisa) {
      $where = "fecha >= " . $this->var2str($desde) . " AND fecha <= " . $this->var2str($hasta);
      if ($codserie)
         $where .= " AND codserie = " . $this->var2str($codserie);

      if ($codagente)
         $where .= " AND codagente = " . $this->var2str($codagente);

      if ($forma_pago)
         $where .= " AND codpago = " . $this->var2str($forma_pago);

      if ($divisa)
         $where .= "AND coddivisa = " . $this->var2str($divisa);

      if ($almacen)
         $where .= "AND codalmacen = " . $this->var2str($almacen);
      
      return $where;
   }   
}
