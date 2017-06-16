<?php

/*
 * This file is part of presupuestos_y_pedidos
 * Copyright (C) 2014-2017  Carlos Garcia Gomez       neorazorx@gmail.com
 * Copyright (C) 2014-2015  Francesc Pineda Segarra   shawe.ewahs@gmail.com
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
require_once __DIR__ . '/pedidos_model_shared.php';

require_model('albaran_proveedor.php');
require_model('proveedor.php');
require_model('linea_pedido_proveedor.php');
require_model('secuencia.php');

/**
 * Pedido de proveedor
 */
class pedido_proveedor extends \fs_model {
   use common_model_shared;
   use pedidos_model_shared;

   /**
    * Clave primaria.
    * @var type 
    */
   public $idpedido;

   /**
    * ID del albarán relacionado.
    * @var type 
    */
   public $idalbaran;

   /**
    * Código del proveedor del pedido.
    * @var type 
    */
   public $codproveedor;

   /**
    * Número del pedido del proveedor. Si lo tiene.
    * @var type 
    */
   public $numproveedor;

   /**
    * Nombre del proveedor.
    * @var type 
    */
   public $nombre;

   /**
    * Indica si se puede editar o no.
    * @var type 
    */
   public $editable;
   
   /**
    * Inicializa con los valores por defecto
    */
   public function clear() {
         $this->idalbaran = NULL;
         $this->codproveedor = NULL;
         $this->numproveedor = NULL;
         $this->nombre = '';

         $this->clear_shared(FALSE);
   }

   /**
    * Inicializa con los valores de un array
    * @param array $data
    */
   public function load_from_data($data) {
      $this->load_from_data_shared($data, FALSE);

      $this->idalbaran = $this->intval($data['idalbaran']);
      $this->codproveedor = $data['codproveedor'];
      $this->numproveedor = $data['numproveedor'];
      $this->nombre = $data['nombre'];

      $this->editable = $this->str2bool($data['editable']);
      if ($this->idalbaran) {
         $this->editable = FALSE;
      }
   }
   
   public function __construct($p = FALSE) {
      parent::__construct('pedidosprov');
      if ($p)
         $this->load_from_data ($p);
      else
         $this->clear();
   }

   protected function install() {
      return '';
   }

   public function url() {
      return $this->url_shared('compras_pedido', $this->idpedido);
   }

   public function albaran_url() {
      return $this->url_shared('compras_albaran', $this->idalbaran);
   }

   public function agente_url() {
      return $this->url_shared('admin_agente', $this->codagente, 'cod');
   }

   public function proveedor_url() {
      return $this->url_shared('compras_proveedor', $this->codproveedor, 'cod');
   }

   public function get_lineas() {
      $linea = new \linea_pedido_proveedor();
      return $linea->all_from_pedido($this->idpedido);
   }

   public function get_versiones() {
      return $this->get_versiones_shared('\pedido_proveedor', 'idpedido', $this->idpedido);
   }

   public function get($id) {
      return $this->get_shared('\pedido_proveedor', 'idpedido', $id);
   }

   public function exists() {
      return $this->exists_shared('idpedido', $this->idpedido);
   }

   public function new_codigo() {
      $this->new_codigo_shared('npedidoprov', FS_PEDIDO);
   }

   /**
    * Comprueba los datos del pedido, devuelve TRUE si está todo correcto
    * @return boolean
    */
   public function test() {
      $this->numproveedor = $this->no_html($this->numproveedor);      
      $this->nombre = $this->no_html($this->nombre);
      if ($this->nombre == '') {
         $this->nombre = '-';
      }

      return $this->test_shared(FALSE);
   }

   public function full_test() {
      $lineas = $this->get_lineas();
      $status = $this->full_test_shared($lineas, FS_PEDIDO);

      if ($this->idalbaran) {
         $alb0 = new \albaran_proveedor();
         $albaran = $alb0->get($this->idalbaran);
         if (!$albaran) {
            $this->idalbaran = NULL;
            $this->editable = TRUE;
            $this->save();
         }
      }

      return $status;
   }

   private function sql_update() {
      $sql = "UPDATE " . $this->table_name . " SET cifnif = " . $this->var2str($this->cifnif)
              . ", codagente = " . $this->var2str($this->codagente)
              . ", codalmacen = " . $this->var2str($this->codalmacen)
              . ", codproveedor = " . $this->var2str($this->codproveedor)
              . ", coddivisa = " . $this->var2str($this->coddivisa)
              . ", codejercicio = " . $this->var2str($this->codejercicio)
              . ", codigo = " . $this->var2str($this->codigo)
              . ", codpago = " . $this->var2str($this->codpago)
              . ", codserie = " . $this->var2str($this->codserie)
              . ", editable = " . $this->var2str($this->editable)
              . ", fecha = " . $this->var2str($this->fecha)
              . ", hora = " . $this->var2str($this->hora)
              . ", idalbaran = " . $this->var2str($this->idalbaran)
              . ", irpf = " . $this->var2str($this->irpf)
              . ", neto = " . $this->var2str($this->neto)
              . ", nombre = " . $this->var2str($this->nombre)
              . ", numero = " . $this->var2str($this->numero)
              . ", numproveedor = " . $this->var2str($this->numproveedor)
              . ", observaciones = " . $this->var2str($this->observaciones)
              . ", tasaconv = " . $this->var2str($this->tasaconv)
              . ", total = " . $this->var2str($this->total)
              . ", totaleuros = " . $this->var2str($this->totaleuros)
              . ", totalirpf = " . $this->var2str($this->totalirpf)
              . ", totaliva = " . $this->var2str($this->totaliva)
              . ", totalrecargo = " . $this->var2str($this->totalrecargo)
              . ", numdocs = " . $this->var2str($this->numdocs)
              . ", idoriginal = " . $this->var2str($this->idoriginal)
              . "  WHERE idpedido = " . $this->var2str($this->idpedido) . ";";

      return $sql;
   }
   
   private function sql_insert() {
      $sql = "INSERT INTO " . $this->table_name . " (cifnif,codagente,codalmacen,codproveedor,
         coddivisa,codejercicio,codigo,codpago,codserie,editable,fecha,hora,idalbaran,irpf,
         neto,nombre,numero,observaciones,tasaconv,total,totaleuros,totalirpf,
         totaliva,totalrecargo,numproveedor,numdocs,idoriginal) VALUES 
               (" . $this->var2str($this->cifnif)
              . "," . $this->var2str($this->codagente)
              . "," . $this->var2str($this->codalmacen)
              . "," . $this->var2str($this->codproveedor)
              . "," . $this->var2str($this->coddivisa)
              . "," . $this->var2str($this->codejercicio)
              . "," . $this->var2str($this->codigo)
              . "," . $this->var2str($this->codpago)
              . "," . $this->var2str($this->codserie)
              . "," . $this->var2str($this->editable)
              . "," . $this->var2str($this->fecha)
              . "," . $this->var2str($this->hora)
              . "," . $this->var2str($this->idalbaran)
              . "," . $this->var2str($this->irpf)
              . "," . $this->var2str($this->neto)
              . "," . $this->var2str($this->nombre)
              . "," . $this->var2str($this->numero)
              . "," . $this->var2str($this->observaciones)
              . "," . $this->var2str($this->tasaconv)
              . "," . $this->var2str($this->total)
              . "," . $this->var2str($this->totaleuros)
              . "," . $this->var2str($this->totalirpf)
              . "," . $this->var2str($this->totaliva)
              . "," . $this->var2str($this->totalrecargo)
              . "," . $this->var2str($this->numproveedor)
              . "," . $this->var2str($this->numdocs) . ","
              . $this->var2str($this->idoriginal) . ");";
      return $sql;
   }
   
   public function save() {
      if ($this->test()) {
         if ($this->exists())
            return $this->db->exec($this->sql_update());
         else {
            $this->new_codigo();
            $ok = $this->db->exec($this->sql_insert());
            if ($ok) {
               $this->idpedido = $this->db->lastval();
               return TRUE;
            } 
         }
      }
      
      return FALSE;
   }

   /**
    * Elimina el pedido de la base de datos.
    * Devuelve FALSE en caso de fallo.
    * @return type
    */
   public function delete() {
      $result = $this->db->exec("DELETE FROM " . $this->table_name . " WHERE idpedido = " . $this->var2str($this->idpedido) . ";");
      if ($result)
         $this->new_message(ucfirst(FS_PEDIDO) . ' de compra ' . $this->codigo . " eliminado correctamente.");
      return $result;
   }

   /**
    * Devuelve un array con los últimos pedidos de compra.
    * @param type $offset
    * @return \pedido_proveedor
    */
   public function all($offset = 0, $order = 'fecha DESC, codigo DESC', $limit = FS_ITEM_LIMIT) {
      return $this->all_shared('\pedido_proveedor', FALSE, $offset, $order, $limit);
   }

   /**
    * Devuelve un array con los pedidos de compra pendientes
    * @param type $offset
    * @param type $order
    * @return \pedido_proveedor
    */
   public function all_ptealbaran($offset = 0, $order = 'fecha ASC, codigo ASC', $limit = FS_ITEM_LIMIT) {
      return $this->all_shared('\pedido_proveedor', 'idalbaran IS NULL', $offset, $order, $limit);
   }

   /**
    * Devuelve un array con todos los pedidos del proveedor.
    * @param type $codproveedor
    * @param type $offset
    * @return \pedido_proveedor
    */
   public function all_from_proveedor($codproveedor, $offset = 0) {
      $where = "codproveedor = " . $this->var2str($codproveedor);
      $order = "fecha DESC, codigo DESC";
      return $this->all_shared('\pedido_proveedor', $where, $offset, $order, FS_ITEM_LIMIT);
   }

   /**
    * Devuelve un array con todos los pedidos del agente/empleado
    * @param type $codagente
    * @param type $offset
    * @return \pedido_proveedor
    */
   public function all_from_agente($codagente, $offset = 0) {
      $where = "codagente = " . $this->var2str($codagente);
      $order = "fecha DESC, codigo DESC";
      return $this->all_shared('\pedido_proveedor', $where, $offset, $order, FS_ITEM_LIMIT);
   }

   /**
    * Devuelve todos los pedidos relacionados con el albarán.
    * @param type $id
    * @return \pedido_proveedor
    */
   public function all_from_albaran($id) {
      $where = "idalbaran = " . $this->var2str($id);
      $order = "fecha DESC, codigo DESC";
      return $this->all_shared('\pedido_proveedor', $where, 0, $order, 0);
   }

   /**
    * 
    * @param type $desde
    * @param type $hasta
    * @param type $codserie
    * @param type $codagente
    * @param type $codproveedor
    * @param type $estado
    * @param type $forma_pago
    * @param type $almacen
    * @param type $divisa
    * @return \pedido_proveedor
    */
   public function all_desde($desde, $hasta, $codserie = FALSE, $codagente = FALSE, $codproveedor = FALSE, $estado = FALSE, $forma_pago = FALSE, $almacen = FALSE, $divisa = FALSE) {
      $where = $this->all_desde_shared($desde, $hasta, $codserie, $codagente, $forma_pago, $almacen, $divisa);
      
      if ($codproveedor)
         $where .= " AND codproveedor = " . $this->var2str($codproveedor);
      
      switch ($estado) {
         case '0': {
            $where .= " AND idalbaran IS NULL ";
            break;
         }
         case '1': {
            $where .= " AND idalbaran IS NOT NULL ";
            break;
         }
         default: {
            break;
         }
      }
      
      return $this->all_shared('\pedido_proveedor', $where, 0, "fecha ASC, codigo ASC", 0);
   }

   /**
    * Devuelve un array con los pedidos que coinciden con $query
    * @param type $query
    * @param type $offset
    * @return \pedido_proveedor
    */
   public function search($query, $offset = 0) {
      $where = $this->search_shared($query, 'numproveedor');
      return $this->all_shared('\pedido_proveedor', $where, $offset, "fecha DESC, codigo DESC", FS_ITEM_LIMIT);
   }

   public function cron_job() {
      $sql = "UPDATE " . $this->table_name . " SET idalbaran = NULL, editable = TRUE"
              . " WHERE idalbaran IS NOT NULL AND NOT EXISTS(SELECT 1 FROM albaranesprov t1 WHERE t1.idalbaran = " . $this->table_name . ".idalbaran);";
      $this->db->exec($sql);
   }

}
