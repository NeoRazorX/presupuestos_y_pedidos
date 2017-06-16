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
trait linea_model_shared {

   /**
    * Referencia del artículo.
    * @var string
    */
   public $referencia;   

   /**
    * Descripcion de la linea
    * @var string
    */
   public $descripcion;

   /**
    * Cantidad de la linea
    * @var float
    */
   public $cantidad;

   /**
    * Código de la combinación seleccionada,
    * en el caso de los artículos con atributos.
    * @var string
    */
   public $codcombinacion;
   
   /**
    * Código del impuesto relacionado.
    * @var string 
    */
   public $codimpuesto;
   
   /**
    * % de descuento.
    * @var float 
    */
   public $dtopor;

   /**
    * % de retención IRPF
    * @var float 
    */
   public $irpf;

   /**
    * % del impuesto relacionado.
    * @var float 
    */
   public $iva;

   /**
    * Importe neto sin descuento, 
    * (pvpunitario * cantidad).
    * @var float 
    */
   public $pvpsindto;

   /**
    * Importe neto de la linea, sin impuestos.
    * @var float 
    */
   public $pvptotal;

   /**
    * Precio de un unidad.
    * @var float 
    */
   public $pvpunitario;

   /**
    * % de recargo de equivalencia RE.
    * @var float
    */
   public $recargo;
   
   /**
    * pvp con impuesto incluido
    * @return float
    */
   public function pvp_iva() {
      return $this->pvpunitario * (100 + $this->iva) / 100;
   }

   /**
    * total con impuesto incluido
    * @return float
    */
   public function total_iva() {
      return $this->pvptotal * (100 + $this->iva - $this->irpf + $this->recargo) / 100;
   }

   /**
    * Descripcion formateada
    * @return string
    */
   public function descripcion() {
      return nl2br($this->descripcion);
   }

   /**
    * Añade un documento a la lista de documentos
    * @param array $docs
    * @param interger $value_id
    * @param string $model
    */
   private function add_document(&$docs, $value_id, $model) {
      $sender = new $model();
      $docs[] = $sender->get($value_id);
   }
   
   /**
    * Busca un campo en el documento con id indicado
    * @param array $docs
    * @param string $model
    * @param string $field
    * @param string $field_id
    * @param integer $value_id
    * @return mixed
    */
   private function search_value_in_document(&$docs, $model, $field, $field_id, $value_id) {
      $result = NULL;
      foreach ($docs as $record) {
         $record_id = $record->$field_id;
         if ($record_id == $value_id) {
            $result = $record->$field;
            break;
         }
      }
      
      if (!isset($result)) {
         $this->add_document($docs, $value_id, $model);
         $index = count($docs) - 1;
         $result = $docs[$index]->$field;
      }
      
      return $result;
   }

   /**
    * Código unificado del método "load_from_data" 
    * en documentos de presupuestos y pedidos
    * @param array $data
    */
   private function load_from_data_shared($data) {
      $this->referencia = $data['referencia'];
      $this->descripcion = $data['descripcion'];
      $this->cantidad = floatval($data['cantidad']);
      $this->codcombinacion = $data['codcombinacion'];
      $this->codimpuesto = $data['codimpuesto'];
      $this->dtopor = floatval($data['dtopor']);
      $this->irpf = floatval($data['irpf']);
      $this->iva = floatval($data['iva']);
      $this->pvpsindto = floatval($data['pvpsindto']);
      $this->pvptotal = floatval($data['pvptotal']);
      $this->pvpunitario = floatval($data['pvpunitario']);
      $this->recargo = floatval($data['recargo']);
   }
   
   /**
    * Código unificado del método "clear" 
    * en documentos de presupuestos y pedidos
    */
   private function clear_shared() {
      $this->referencia = NULL;
      $this->descripcion = '';
      $this->cantidad = 0;
      $this->codcombinacion = NULL;      
      $this->codimpuesto = NULL;
      $this->dtopor = 0;
      $this->irpf = 0;
      $this->iva = 0;
      $this->pvpsindto = 0;
      $this->pvptotal = 0;
      $this->pvpunitario = 0;
      $this->recargo = 0;
   }

   /**
    * Código unificado del método "test" 
    * en documentos de presupuestos y pedidos
    * @param string $text
    * @return boolean
    */
   private function test_shared($text) {
      $this->descripcion = $this->no_html($this->descripcion);
      $total = $this->pvpunitario * $this->cantidad * (100 - $this->dtopor) / 100;
      $totalsindto = $this->pvpunitario * $this->cantidad;
      
      if (!$this->floatcmp($this->pvptotal, $total, FS_NF0, TRUE)) {
         $this->new_error_msg("Error en el valor de pvptotal de la línea " . $this->referencia . " del " . $text . ". Valor correcto: " . $total);
         return FALSE;
      }
      
      if (!$this->floatcmp($this->pvpsindto, $totalsindto, FS_NF0, TRUE)) {
         $this->new_error_msg("Error en el valor de pvpsindto de la línea " . $this->referencia . " del " . $text . ". Valor correcto: " . $totalsindto);
         return FALSE;
      }
      
      return TRUE;
   }   
   
   /**
    * Código unificado del método "search" 
    * en documentos de presupuestos y pedidos
    * @param string $query
    * @return string
    */
   private function search_shared($query) {
      $value = mb_strtolower($this->no_html($query), 'UTF8');

      $where = "";
      if (is_numeric($value))
         $where = "referencia LIKE '%" . $value . "%' OR descripcion LIKE '%" . $value . "%'";
      else {
         $buscar = str_replace(' ', '%', $value);
         $where = "lower(referencia) LIKE '%" . $buscar . "%' OR lower(descripcion) LIKE '%" . $buscar . "%'";
      }

      return $where;
   }      
}
