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
require_once __DIR__ . '/pedidos_model_shared.php';

require_model('albaran_cliente.php');
require_model('cliente.php');
require_model('linea_pedido_cliente.php');
require_model('secuencia.php');

/**
 * Pedido de cliente
 */
class pedido_cliente extends \fs_model {
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
    * Código del cliente del pedido.
    * @var type 
    */
   public $codcliente;

   /**
    * País del cliente.
    * @var type 
    */
   public $codpais;

   /**
    * ID de la dirección del cliente.
    * Modelo direccion_cliente.
    * @var type 
    */
   public $coddir;
   public $codpostal;

   /**
    * Número opcional a disposición del usuario.
    * @var type 
    */
   public $numero2;
   public $nombrecliente;
   public $direccion;
   public $ciudad;
   public $provincia;
   public $apartado;

   /**
    * % de comisión del empleado.
    * @var type 
    */
   public $porcomision;


   /**
    * Estado del pedido:
    * 0 -> pendiente. (editable)
    * 1 -> aprobado. (hay un idalbaran y no es editable)
    * 2 -> rechazado. (no hay idalbaran y no es editable)
    * @var type 
    */
   public $status;
   public $editable;

   /**
    * Fecha en la que se envió el pedido por email.
    * @var type 
    */
   public $femail;

   /**
    * Fecha de salida prevista del material.
    * @var type 
    */
   public $fechasalida;
 
   /// datos de transporte
   public $envio_codtrans;
   public $envio_codigo;
   public $envio_nombre;
   public $envio_apellidos;
   public $envio_apartado;
   public $envio_direccion;
   public $envio_codpostal;
   public $envio_ciudad;
   public $envio_provincia;
   public $envio_codpais;

   /**
    * Inicializa con los valores por defecto
    */
   public function clear() {
      $this->idalbaran = NULL;
      $this->fechasalida = NULL;

      $this->clear_shared(TRUE);
   }
   
   /**
    * Inicializa con los valores de un array
    * @param array $data
    */
   public function load_from_data($data) {
      $this->load_from_data_shared($data, TRUE);

      /// calculamos el estado para mantener compatibilidad con eneboo
      $this->idalbaran = $this->intval($data['idalbaran']);
      if ($this->idalbaran) {
         $this->status = 1;
         $this->editable = FALSE;
      }
      else {
         $this->status = intval($data['status']);
         if ($this->status == 2)
            $this->editable = FALSE;         /// cancelado
         else {
            $this->editable = $this->str2bool($data['editable']);
            $this->status = $this->editable ? 0 : 2;
         }
      }

      $this->fechasalida = NULL;
      if (!is_null($data['fechasalida'])) {
         $this->fechasalida = Date('d-m-Y', strtotime($data['fechasalida']));
      }
   }
   
   public function __construct($p = FALSE) {
      parent::__construct('pedidoscli');
      if ($p)
         $this->load_from_data ($p);
      else
         $this->clear();
   }

   protected function install() {
      return '';
   }

   public function url() {
      return $this->url_shared('ventas_pedido', $this->idpedido);
   }

   public function albaran_url() {
      return $this->url_shared('ventas_albaran', $this->idalbaran);
   }

   public function agente_url() {
      return $this->url_shared('admin_agente', $this->codagente, 'cod');
   }

   public function cliente_url() {
      return $this->url_shared('ventas_cliente', $this->codcliente, 'cod');
   }

   /**
    * Devuelve las líneas del pedido.
    * @return \linea_pedido_cliente
    */
   public function get_lineas() {
      $linea = new \linea_pedido_cliente();
      return $linea->all_from_pedido($this->idpedido);
   }

   public function get_versiones() {
      return $this->get_versiones_shared('\pedido_cliente', 'idpedido', $this->idpedido);
   }

   public function get($id) {
      return $this->get_shared('\pedido_cliente', 'idpedido', $id);
   }

   public function exists() {
      return $this->exists_shared('idpedido', $this->idpedido);
   }

   /**
    * Genera un nuevo código y número para el pedido
    */
   public function new_codigo() {
      $this->new_codigo_shared('npedidocli', FS_PEDIDO);
   }

   /**
    * Comprueba los datos del pedido, devuelve TRUE si está todo correcto
    * @return boolean
    */
   public function test() {
      /// comprobamos que editable se corresponda con el status
      if ($this->idalbaran)
         $this->status = 1;
      
      $this->editable = ($this->status == 0);
      
      return $this->test_shared(TRUE);
   }

   public function full_test() {
      $lineas = $this->get_lineas();
      $status = $this->full_test_shared($lineas, FS_PEDIDO);

      if ($this->idalbaran) {
         $alb0 = new \albaran_cliente();
         $albaran = $alb0->get($this->idalbaran);
         if (!$albaran) {
            $this->idalbaran = NULL;
            $this->status = 0;
            $this->editable = TRUE;
            $this->save();
         }
      }

      return $status;
   }

   private function sql_update() {
      $sql = "UPDATE " . $this->table_name . " SET apartado = " . $this->var2str($this->apartado)
              . ", cifnif = " . $this->var2str($this->cifnif)
              . ", ciudad = " . $this->var2str($this->ciudad)
              . ", codagente = " . $this->var2str($this->codagente)
              . ", codalmacen = " . $this->var2str($this->codalmacen)
              . ", codcliente = " . $this->var2str($this->codcliente)
              . ", coddir = " . $this->var2str($this->coddir)
              . ", coddivisa = " . $this->var2str($this->coddivisa)
              . ", codejercicio = " . $this->var2str($this->codejercicio)
              . ", codigo = " . $this->var2str($this->codigo)
              . ", codpago = " . $this->var2str($this->codpago)
              . ", codpais = " . $this->var2str($this->codpais)
              . ", codpostal = " . $this->var2str($this->codpostal)
              . ", codserie = " . $this->var2str($this->codserie)
              . ", direccion = " . $this->var2str($this->direccion)
              . ", editable = " . $this->var2str($this->editable)
              . ", fecha = " . $this->var2str($this->fecha)
              . ", hora = " . $this->var2str($this->hora)
              . ", idalbaran = " . $this->var2str($this->idalbaran)
              . ", irpf = " . $this->var2str($this->irpf)
              . ", neto = " . $this->var2str($this->neto)
              . ", nombrecliente = " . $this->var2str($this->nombrecliente)
              . ", numero = " . $this->var2str($this->numero)
              . ", numero2 = " . $this->var2str($this->numero2)
              . ", observaciones = " . $this->var2str($this->observaciones)
              . ", status = " . $this->var2str($this->status)
              . ", porcomision = " . $this->var2str($this->porcomision)
              . ", provincia = " . $this->var2str($this->provincia)
              . ", tasaconv = " . $this->var2str($this->tasaconv)
              . ", total = " . $this->var2str($this->total)
              . ", totaleuros = " . $this->var2str($this->totaleuros)
              . ", totalirpf = " . $this->var2str($this->totalirpf)
              . ", totaliva = " . $this->var2str($this->totaliva)
              . ", totalrecargo = " . $this->var2str($this->totalrecargo)
              . ", femail = " . $this->var2str($this->femail)
              . ", fechasalida = " . $this->var2str($this->fechasalida)
              . ", codtrans = " . $this->var2str($this->envio_codtrans)
              . ", codigoenv = " . $this->var2str($this->envio_codigo)
              . ", nombreenv = " . $this->var2str($this->envio_nombre)
              . ", apellidosenv = " . $this->var2str($this->envio_apellidos)
              . ", apartadoenv = " . $this->var2str($this->envio_apartado)
              . ", direccionenv = " . $this->var2str($this->envio_direccion)
              . ", codpostalenv = " . $this->var2str($this->envio_codpostal)
              . ", ciudadenv = " . $this->var2str($this->envio_ciudad)
              . ", provinciaenv = " . $this->var2str($this->envio_provincia)
              . ", codpaisenv = " . $this->var2str($this->envio_codpais)
              . ", numdocs = " . $this->var2str($this->numdocs)
              . ", idoriginal = " . $this->var2str($this->idoriginal)
              . "  WHERE idpedido = " . $this->var2str($this->idpedido) . ";";
      return $sql;
   }
   
   private function sql_insert() {
      $sql = "INSERT INTO " . $this->table_name . " (apartado,cifnif,ciudad,codagente,codalmacen,
         codcliente,coddir,coddivisa,codejercicio,codigo,codpais,codpago,codpostal,codserie,
         direccion,editable,fecha,hora,idalbaran,irpf,neto,nombrecliente,numero,observaciones,
         status,porcomision,provincia,tasaconv,total,totaleuros,totalirpf,totaliva,totalrecargo,
         numero2,femail,fechasalida,codtrans,codigoenv,nombreenv,apellidosenv,apartadoenv,direccionenv,
         codpostalenv,ciudadenv,provinciaenv,codpaisenv,numdocs,idoriginal) VALUES ("
              . $this->var2str($this->apartado) . ","
              . $this->var2str($this->cifnif) . ","
              . $this->var2str($this->ciudad) . ","
              . $this->var2str($this->codagente) . ","
              . $this->var2str($this->codalmacen) . ","
              . $this->var2str($this->codcliente) . ","
              . $this->var2str($this->coddir) . ","
              . $this->var2str($this->coddivisa) . ","
              . $this->var2str($this->codejercicio) . ","
              . $this->var2str($this->codigo) . ","
              . $this->var2str($this->codpais) . ","
              . $this->var2str($this->codpago) . ","
              . $this->var2str($this->codpostal) . ","
              . $this->var2str($this->codserie) . ","
              . $this->var2str($this->direccion) . ","
              . $this->var2str($this->editable) . ","
              . $this->var2str($this->fecha) . ","
              . $this->var2str($this->hora) . ","
              . $this->var2str($this->idalbaran) . ","
              . $this->var2str($this->irpf) . ","
              . $this->var2str($this->neto) . ","
              . $this->var2str($this->nombrecliente) . ","
              . $this->var2str($this->numero) . ","
              . $this->var2str($this->observaciones) . ","
              . $this->var2str($this->status) . ","
              . $this->var2str($this->porcomision) . ","
              . $this->var2str($this->provincia) . ","
              . $this->var2str($this->tasaconv) . ","
              . $this->var2str($this->total) . ","
              . $this->var2str($this->totaleuros) . ","
              . $this->var2str($this->totalirpf) . ","
              . $this->var2str($this->totaliva) . ","
              . $this->var2str($this->totalrecargo) . ","
              . $this->var2str($this->numero2) . ","
              . $this->var2str($this->femail) . ","
              . $this->var2str($this->fechasalida) . ","
              . $this->var2str($this->envio_codtrans) . ","
              . $this->var2str($this->envio_codigo) . ","
              . $this->var2str($this->envio_nombre) . ","
              . $this->var2str($this->envio_apellidos) . ","
              . $this->var2str($this->envio_apartado) . ","
              . $this->var2str($this->envio_direccion) . ","
              . $this->var2str($this->envio_codpostal) . ","
              . $this->var2str($this->envio_ciudad) . ","
              . $this->var2str($this->envio_provincia) . ","
              . $this->var2str($this->envio_codpais) . ","
              . $this->var2str($this->numdocs) . ","
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
    * @return boolean
    */
   public function delete() {
      $result = $this->db->exec("DELETE FROM " . $this->table_name . " WHERE idpedido = " . $this->var2str($this->idpedido) . ";");
      if ($result) {
         /// modificamos el presupuesto relacionado
         $this->db->exec("UPDATE presupuestoscli SET idpedido = NULL, editable = TRUE,"
                 . " status = 0 WHERE idpedido = " . $this->var2str($this->idpedido) . ";");

         $this->new_message(ucfirst(FS_PEDIDO) . ' de venta ' . $this->codigo . " eliminado correctamente.");
      }
      
      return $result;
   }

   /**
    * Devuelve un array con los pedidos de venta.
    * @param type $offset
    * @param type $order
    * @return \pedido_cliente
    */
   public function all($offset = 0, $order = 'fecha DESC', $limit = FS_ITEM_LIMIT) {
      return $this->all_shared('\pedido_cliente', FALSE, $offset, $order, $limit);
   }

   /**
    * Devuelve un array con los pedidos de venta pendientes
    * @param type $offset
    * @param type $order
    * @return \pedido_cliente
    */
   public function all_ptealbaran($offset = 0, $order = 'fecha ASC', $limit = FS_ITEM_LIMIT) {
      $where = "idalbaran IS NULL AND status = 0";
      return $this->all_shared('\pedido_cliente', $where, $offset, $order, $limit);
   }

   /**
    * Devuelve un array con los pedidos de venta rechazados
    * @param type $offset
    * @param type $order
    * @return \pedido_cliente
    */
   public function all_rechazados($offset = 0, $order = 'fecha DESC', $limit = FS_ITEM_LIMIT) {
      $where = "status = 2";
      return $this->all_shared('\pedido_cliente', $where, $offset, $order, $limit);
   }

   /**
    * Devuelve un array con los pedidos del cliente $codcliente
    * @param type $codcliente
    * @param type $offset
    * @return \pedido_cliente
    */
   public function all_from_cliente($codcliente, $offset = 0) {
      $where = "codcliente = " . $this->var2str($codcliente);
      $order = "fecha DESC, codigo DESC";
      return $this->all_shared('\pedido_cliente', $where, $offset, $order, FS_ITEM_LIMIT);
   }

   /**
    * Devuelve un array con los pedidos del agente/empleado
    * @param type $codagente
    * @param type $offset
    * @return \pedido_cliente
    */
   public function all_from_agente($codagente, $offset = 0) {
      $where = "codagente = " . $this->var2str($codagente);
      $order = "fecha DESC, codigo DESC";
      return $this->all_shared('\pedido_cliente', $where, $offset, $order, FS_ITEM_LIMIT);
   }

   /**
    * Devuelve todos los pedidos relacionados con el albarán.
    * @param type $id
    * @return \pedido_cliente
    */
   public function all_from_albaran($id) {
      $where = "idalbaran = " . $this->var2str($id);
      $order = "fecha DESC, codigo DESC";
      return $this->all_shared('\pedido_cliente', $where, 0, $order, 0);
   }

   /**
    * 
    * @param type $desde
    * @param type $hasta
    * @param type $codserie
    * @param type $codagente
    * @param type $codcliente
    * @param type $estado
    * @param type $forma_pago
    * @param type $almacen
    * @param type $divisa
    * @return \presupuesto_cliente
    */
   public function all_desde($desde, $hasta, $codserie = FALSE, $codagente = FALSE, $codcliente = FALSE, $estado = FALSE, $forma_pago = FALSE, $almacen = FALSE, $divisa = FALSE) {
      $where = $this->all_desde_shared($desde, $hasta, $codserie, $codagente, $forma_pago, $almacen, $divisa);

      if ($codcliente)
         $where .= " AND codcliente = " . $this->var2str($codcliente);

      if ($estado) {
         $where .= " AND status = " . $this->var2str($estado);
         if ($estado == "0")
            $where .= " AND idalbaran IS NULL ";
      }
      
      return $this->all_shared('\pedido_cliente', $where, 0, "fecha ASC, codigo ASC", 0);
   }

   /**
    * Devuelve un array con todos los pedidos que coinciden con $query
    * @param type $query
    * @param type $offset
    * @return \pedido_cliente
    */
   public function search($query, $offset = 0) {
      $where = $this->search_shared($query, 'numero2');
      return $this->all_shared('\pedido_cliente', $where, $offset, "fecha DESC, codigo DESC", FS_ITEM_LIMIT);
   }

   /**
    * Devuelve un array con todos los pedidos que coincicen con $query del cliente $codcliente
    * @param type $codcliente
    * @param type $desde
    * @param type $hasta
    * @param type $serie
    * @param type $obs
    * @return \pedido_cliente
    */
   public function search_from_cliente($codcliente, $desde, $hasta, $serie, $obs = '') {
      $where = "codcliente = " . $this->var2str($codcliente)
            . " AND idalbaran"
            . " AND fecha BETWEEN " . $this->var2str($desde) . " AND " . $this->var2str($hasta)
            . " AND codserie = " . $this->var2str($serie);

      if ($obs != '')
         $where .= " AND lower(observaciones) = " . $this->var2str(strtolower($obs));      

      return $this->all_shared('\pedido_cliente', $where, 0, "fecha DESC, codigo DESC", 0);
   }

   public function cron_job() {
      /// marcamos como aprobados los presupuestos con idpedido
      $this->db->exec("UPDATE " . $this->table_name . " SET status = '1', editable = FALSE"
              . " WHERE status != '1' AND idalbaran IS NOT NULL;");

      /// devolvemos al estado pendiente a los pedidos con estado 1 a los que se haya borrado el albarán
      $this->db->exec("UPDATE " . $this->table_name . " SET status = '0', idalbaran = NULL, editable = TRUE "
              . "WHERE status = '1' AND idalbaran NOT IN (SELECT idalbaran FROM albaranescli);");

      /// marcamos como rechazados todos los presupuestos no editables y sin pedido asociado
      $this->db->exec("UPDATE pedidoscli SET status = '2' WHERE idalbaran IS NULL AND"
              . " editable = false;");
   }
}
