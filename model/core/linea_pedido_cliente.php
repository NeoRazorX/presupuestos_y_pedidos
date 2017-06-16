<?php

/*
 * This file is part of presupuestos_y_pedidos
 * Copyright (C) 2014-2017    Carlos Garcia Gomez        neorazorx@gmail.com
 * Copyright (C) 2014         Francesc Pineda Segarra    shawe.ewahs@gmail.com
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

require_once __DIR__ . '/common_model_shared.php';
require_once __DIR__ . '/linea_model_shared.php';

require_model('pedido_cliente.php');

/**
 * Línea de pedido de cliente.
 * 
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class linea_pedido_cliente extends \fs_model {
   use common_model_shared;
   use linea_model_shared;
   
   /**
    * Clave primaria.
    * @var integer
    */
   public $idlinea;

   /**
    * ID de la linea relacionada en el presupuesto relacionado,
    * si lo hay.
    * @var integer
    */
   public $idlineapresupuesto;

   /**
    * ID del pedido.
    * @var integer
    */
   public $idpedido;

   /**
    * ID del presupuesto relacionado, si lo hay.
    * @var integer
    */
   public $idpresupuesto;
   
   /**
    * Posición de la linea en el documento. Cuanto más alto más abajo.
    * @var type 
    */
   public $orden;

   /**
    * False -> no se muestra la columna cantidad al imprimir.
    * @var type 
    */
   public $mostrar_cantidad;

   /**
    * False -> no se muestran las columnas precio, descuento, impuestos y total al imprimir.
    * @var type 
    */
   public $mostrar_precio;
   private static $pedidos;

   /**
    * Carga los valores de las propiedades del modelo 
    * en base al array informado
    * @param array $data
    */
   public function load_from_data($data) {
      $this->load_from_data_shared($data);
      
      $this->idlinea = $this->intval($data['idlinea']);
      $this->idlineapresupuesto = $this->intval($data['idlineapresupuesto']);
      $this->idpedido = $this->intval($data['idpedido']);
      $this->idpresupuesto = $this->intval($data['idpresupuesto']);
      $this->orden = intval($data['orden']);
      $this->mostrar_cantidad = $this->str2bool($data['mostrar_cantidad']);
      $this->mostrar_precio = $this->str2bool($data['mostrar_precio']);      
   }
   
   /**
    * Inicializa las propiedades del modelo
    */
   public function clear() {
      $this->clear_shared();
      
      $this->idlinea = NULL;
      $this->idlineapresupuesto = NULL;
      $this->idpedido = NULL;
      $this->idpresupuesto = NULL;
      $this->orden = 0;
      $this->mostrar_cantidad = TRUE;
      $this->mostrar_precio = TRUE;
   }
   
   public function __construct($l = FALSE) {
      parent::__construct('lineaspedidoscli');

      if (!isset(self::$pedidos))
         self::$pedidos = array();

      if ($l)
         $this->load_from_data ($l);
      else
         $this->clear();
   }

   protected function install() {
      return '';
   }

   public function show_codigo() {
      return $this->search_value_in_document(
                  self::$pedidos, 
                  '\pedido_cliente', 
                  'codigo', 
                  'idpedido', 
                  $this->idpedido);
   }

   public function show_fecha() {
      return $this->search_value_in_document(
                  self::$pedidos, 
                  '\pedido_cliente', 
                  'fecha', 
                  'idpedido', 
                  $this->idpedido);
   }

   public function show_nombrecliente() {
      return $this->search_value_in_document(
                  self::$pedidos, 
                  '\pedido_cliente', 
                  'nombrecliente', 
                  'idpedido', 
                  $this->idpedido);
   }

   /**
    * URL de la página
    * @return string
    */
   public function url() {
      return $this->url_shared('ventas_pedido', $this->idpedido);
   }

   /**
    * URL para el artículo
    * @return string
    */
   public function articulo_url() {
      return $this->url_shared('ventas_articulo', urlencode($this->referencia), 'ref');
   }

   /**
    * Comprueba si existe la linea
    * @return boolean
    */
   public function exists() {
      return $this->exists_shared('idlinea', $this->idlinea);
   }

   /**
    * Comprueba si se puede grabar la linea
    * @return boolean
    */
   public function test() {
      return $this->test_shared(FS_PEDIDO);
   }

   /**
    * Sentencia SQL para UPDATE de la linea
    * @return string
    */
   private function sql_update() {
      $sql = "UPDATE " . $this->table_name . " SET cantidad = " . $this->var2str($this->cantidad)
              . ", codimpuesto = " . $this->var2str($this->codimpuesto)
              . ", descripcion = " . $this->var2str($this->descripcion)
              . ", dtopor = " . $this->var2str($this->dtopor)
              . ", idpedido = " . $this->var2str($this->idpedido)
              . ", idlineapresupuesto = " . $this->var2str($this->idlineapresupuesto)
              . ", idpresupuesto = " . $this->var2str($this->idpresupuesto)
              . ", irpf = " . $this->var2str($this->irpf)
              . ", iva = " . $this->var2str($this->iva)
              . ", pvpsindto = " . $this->var2str($this->pvpsindto)
              . ", pvptotal = " . $this->var2str($this->pvptotal)
              . ", pvpunitario = " . $this->var2str($this->pvpunitario)
              . ", recargo = " . $this->var2str($this->recargo)
              . ", referencia = " . $this->var2str($this->referencia)
              . ", codcombinacion = " . $this->var2str($this->codcombinacion)
              . ", orden = " . $this->var2str($this->orden)
              . ", mostrar_cantidad = " . $this->var2str($this->mostrar_cantidad)
              . ", mostrar_precio = " . $this->var2str($this->mostrar_precio)
              . "  WHERE idlinea = " . $this->var2str($this->idlinea) . ";";
      return $sql;
   }

   /**
    * Sentencia SQL para INSERT de una nueva la linea
    * @return string
    */
   private function sql_insert() {
      $sql = "INSERT INTO " . $this->table_name . " (cantidad,codimpuesto,descripcion,dtopor,idpedido,
         irpf,iva,pvpsindto,pvptotal,pvpunitario,recargo,referencia,codcombinacion,idlineapresupuesto,
         idpresupuesto,orden,mostrar_cantidad,mostrar_precio) VALUES (" . $this->var2str($this->cantidad)
              . "," . $this->var2str($this->codimpuesto)
              . "," . $this->var2str($this->descripcion)
              . "," . $this->var2str($this->dtopor)
              . "," . $this->var2str($this->idpedido)
              . "," . $this->var2str($this->irpf)
              . "," . $this->var2str($this->iva)
              . "," . $this->var2str($this->pvpsindto)
              . "," . $this->var2str($this->pvptotal)
              . "," . $this->var2str($this->pvpunitario)
              . "," . $this->var2str($this->recargo)
              . "," . $this->var2str($this->referencia)
              . "," . $this->var2str($this->codcombinacion)
              . "," . $this->var2str($this->idlineapresupuesto)
              . "," . $this->var2str($this->idpresupuesto)
              . "," . $this->var2str($this->orden)
              . "," . $this->var2str($this->mostrar_cantidad)
              . "," . $this->var2str($this->mostrar_precio) . ");";
      return $sql;
   }
   
   /**
    * Graba los datos de la linea
    * @return boolean
    */
   public function save() {
      if ($this->test()) {
         if ($this->exists())
            return $this->db->exec($this->sql_update());
         else {
            $ok = $this->db->exec($this->sql_insert());
            if ($ok) {
               $this->idlinea = $this->db->lastval();
               return TRUE;
            }
         }
      }
      
      return FALSE;
   }

   /**
    * Elimina la linea de documento
    * @return boolean
    */
   public function delete() {
      return $this->db->exec("DELETE FROM " . $this->table_name . " WHERE idlinea = " . $this->var2str($this->idlinea) . ";");
   }

   /**
    * Devuelve todas las líneas del pedido $idp
    * @param type $idp
    * @return \linea_pedido_cliente
    */
   public function all_from_pedido($idp) {
      $where = "idpedido = " . $this->var2str($idp);
      $order = "orden DESC, idlinea ASC";
      return $this->all_shared('\linea_pedido_cliente', $where, 0, $order, 0);
   }

   /**
    * Devuelve todas las líneas que hagan referencia al artículo $ref
    * @param type $ref
    * @param type $offset
    * @param type $limit
    * @return \linea_pedido_cliente
    */
   public function all_from_articulo($ref, $offset = 0, $limit = FS_ITEM_LIMIT) {
      $where = "referencia = " . $this->var2str($ref);
      return $this->all_shared('\linea_pedido_cliente', $where, $offset, "idpedido DESC", $limit);
   }

   /**
    * Busca todas las coincidencias de $query en las líneas.
    * @param type $query
    * @param type $offset
    * @return \linea_pedido_cliente
    */
   public function search($query = '', $offset = 0) {
      $where = $this->search_shared($query);
      $order = "idpedido DESC, idlinea ASC";
      return $this->all_shared('\linea_pedido_cliente', $where, $offset, $order, FS_ITEM_LIMIT);
   }
}
