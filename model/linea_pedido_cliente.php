<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2014-2015  Carlos Garcia Gomez  neorazorx@gmail.com
 * Copyright (C) 2014  Francesc Pineda Segarra  shawe.ewahs@gmail.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 * 
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

class linea_pedido_cliente extends fs_model
{
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
   
   public $cantidad;
   
   /**
    * Impuesto relacionado.
    * @var type 
    */
   public $codimpuesto;
   
   public $descripcion;
   
   /**
    * % de descuento
    * @var type 
    */
   public $dtopor;
   
   /**
    * % de retención IRPF.
    * @var type 
    */
   public $irpf;
   
   /**
    * % del impuesto relacionado.
    * @var type 
    */
   public $iva;
   
   /**
    * Precio sin descuento, ni impuestos.
    * @var type 
    */
   public $pvpsindto;
   
   /**
    * Precio total, pero sin impuestos.
    * @var type 
    */
   public $pvptotal;
   
   /**
    * Precio de una unidad.
    * @var type 
    */
   public $pvpunitario;
   
   /**
    * % de recargo.
    * @var type 
    */
   public $recargo;
   
   /**
    * Referencia del artículo.
    * @var type 
    */
   public $referencia;
   
   private static $pedidos;
   
   public function __construct($l = FALSE)
   {
      parent::__construct('lineaspedidoscli', 'plugins/presupuestos_y_pedidos/');
      
      if( !isset(self::$pedidos) )
         self::$pedidos = array();
      
      if($l)
      {
         $this->cantidad = floatval($l['cantidad']);
         $this->codimpuesto = $l['codimpuesto'];
         $this->descripcion = $l['descripcion'];
         $this->dtopor = floatval($l['dtopor']);
         $this->idlinea = $this->intval($l['idlinea']);
         $this->idlineapresupuesto = $this->intval($l['idlineapresupuesto']);
         $this->idpedido = $this->intval($l['idpedido']);
         $this->idpresupuesto = $this->intval($l['idpresupuesto']);
         $this->irpf = floatval($l['irpf']);
         $this->iva = floatval($l['iva']);
         $this->pvpsindto = floatval($l['pvpsindto']);
         $this->pvptotal = floatval($l['pvptotal']);
         $this->pvpunitario = floatval($l['pvpunitario']);
         $this->recargo = floatval($l['recargo']);
         $this->referencia = $l['referencia'];
      }
      else
      {
         $this->cantidad = 0;
         $this->codimpuesto = NULL;
         $this->descripcion = '';
         $this->dtopor = 0;
         $this->idlinea = NULL;
         $this->idlineapresupuesto = NULL;
         $this->idpedido = NULL;
         $this->idpresupuesto = NULL;
         $this->irpf = 0;
         $this->iva = 0;
         $this->pvpsindto = 0;
         $this->pvptotal = 0;
         $this->pvpunitario = 0;
         $this->recargo = 0;
         $this->referencia = NULL;
      }
   }
   
   protected function install()
   {
      return '';
   }
   
   public function pvp_iva()
   {
      return $this->pvpunitario*(100+$this->iva)/100;
   }
   
   public function total_iva()
   {
      return $this->pvptotal*(100+$this->iva-$this->irpf+$this->recargo)/100;
   }
   
   public function descripcion()
   {
      return nl2br($this->descripcion);
   }
   
   public function show_codigo()
   {
      $codigo = 'desconocido';
      
      $encontrado = FALSE;
      foreach(self::$pedidos as $p)
      {
         if($p->idpedido == $this->idpedido)
         {
            $codigo = $p->codigo;
            $encontrado = TRUE;
            break;
         }
      }
      
      if( !$encontrado )
      {
         $pre = new pedido_cliente();
         self::$pedidos[] = $pre->get($this->idpedido);
         $codigo = self::$pedidos[ count(self::$pedidos)-1 ]->codigo;
      }
      
      return $codigo;
   }
   
   public function show_fecha()
   {
      $fecha = 'desconocida';
      
      $encontrado = FALSE;
      foreach(self::$pedidos as $p)
      {
         if($p->idpedido == $this->idpedido)
         {
            $fecha = $p->fecha;
            $encontrado = TRUE;
            break;
         }
      }
      
      if( !$encontrado )
      {
         $pre = new pedido_cliente();
         self::$pedidos[] = $pre->get($this->idpedido);
         $fecha = self::$pedidos[ count(self::$pedidos)-1 ]->fecha;
      }
      
      return $fecha;
   }
   
   public function show_nombrecliente()
   {
      $nombre = 'desconocido';
      
      $encontrado = FALSE;
      foreach(self::$pedidos as $p)
      {
         if($p->idpedido == $this->idpedido)
         {
            $nombre = $p->nombrecliente;
            $encontrado = TRUE;
            break;
         }
      }
      
      if( !$encontrado )
      {
         $pre = new pedido_cliente();
         self::$pedidos[] = $pre->get($this->idpedido);
         $nombre = self::$pedidos[ count(self::$pedidos)-1 ]->nombrecliente;
      }
      
      return $nombre;
   }
   
   public function url()
   {
      return 'index.php?page=ventas_pedido&id='.$this->idpedido;
   }
   
   public function articulo_url()
   {
      if( is_null($this->referencia) OR $this->referencia == '')
      {
         return "index.php?page=ventas_articulos";
      }
      else
         return "index.php?page=ventas_articulo&ref=".urlencode($this->referencia);
   }
   
   public function exists()
   {
      if( is_null($this->idlinea) )
      {
         return FALSE;
      }
      else
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE idlinea = ".$this->var2str($this->idlinea).";");
   }
   
   public function test()
   {
      $this->descripcion = $this->no_html($this->descripcion);
      $total = $this->pvpunitario * $this->cantidad * (100 - $this->dtopor) / 100;
      $totalsindto = $this->pvpunitario * $this->cantidad;
      
      if( !$this->floatcmp($this->pvptotal, $total, FS_NF0, TRUE) )
      {
         $this->new_error_msg("Error en el valor de pvptotal de la línea ".$this->referencia." del ".FS_PEDIDO.". Valor correcto: ".$total);
         return FALSE;
      }
      else if( !$this->floatcmp($this->pvpsindto, $totalsindto, FS_NF0, TRUE) )
      {
         $this->new_error_msg("Error en el valor de pvpsindto de la línea ".$this->referencia." del ".FS_PEDIDO.". Valor correcto: ".$totalsindto);
         return FALSE;
      }
      else
         return TRUE;
   }
   
   public function save()
   {
      if( $this->test() )
      {
         if( $this->exists() )
         {
            $sql = "UPDATE ".$this->table_name." SET cantidad = ".$this->var2str($this->cantidad)
                    .", codimpuesto = ".$this->var2str($this->codimpuesto)
                    .", descripcion = ".$this->var2str($this->descripcion)
                    .", dtopor = ".$this->var2str($this->dtopor)
                    .", idpedido = ".$this->var2str($this->idpedido)
                    .", idlineapresupuesto = ".$this->var2str($this->idlineapresupuesto)
                    .", idpresupuesto = ".$this->var2str($this->idpresupuesto)
                    .", irpf = ".$this->var2str($this->irpf)
                    .", iva = ".$this->var2str($this->iva)
                    .", pvpsindto = ".$this->var2str($this->pvpsindto)
                    .", pvptotal = ".$this->var2str($this->pvptotal)
                    .", pvpunitario = ".$this->var2str($this->pvpunitario)
                    .", recargo = ".$this->var2str($this->recargo)
                    .", referencia = ".$this->var2str($this->referencia)
                    ."  WHERE idlinea = ".$this->var2str($this->idlinea).";";
            
            return $this->db->exec($sql);
         }
         else
         {
            $sql = "INSERT INTO ".$this->table_name." (cantidad,codimpuesto,descripcion,dtopor,idpedido,
               irpf,iva,pvpsindto,pvptotal,pvpunitario,recargo,referencia,idlineapresupuesto,idpresupuesto)
               VALUES (".$this->var2str($this->cantidad)
                    .",".$this->var2str($this->codimpuesto)
                    .",".$this->var2str($this->descripcion)
                    .",".$this->var2str($this->dtopor)
                    .",".$this->var2str($this->idpedido)
                    .",".$this->var2str($this->irpf)
                    .",".$this->var2str($this->iva)
                    .",".$this->var2str($this->pvpsindto)
                    .",".$this->var2str($this->pvptotal)
                    .",".$this->var2str($this->pvpunitario)
                    .",".$this->var2str($this->recargo)
                    .",".$this->var2str($this->referencia)
                    .",".$this->var2str($this->idlineapresupuesto)
                    .",".$this->var2str($this->idpresupuesto).");";
            
            if( $this->db->exec($sql) )
            {
               $this->idlinea = $this->db->lastval();
               return TRUE;
            }
            else
               return FALSE;
         }
      }
      else
         return FALSE;
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE idlinea = ".$this->var2str($this->idlinea).";");
   }
   
   public function all_from_pedido($idp)
   {
      $plist = array();
      
      $data = $this->db->select("SELECT * FROM ".$this->table_name." WHERE idpedido = ".$this->var2str($idp)." ORDER BY idlinea ASC;");
      if($data)
      {
         foreach($data as $d)
            $plist[] = new linea_pedido_cliente($d);
      }
      
      return $plist;
   }
   
   public function all_from_articulo($ref, $offset=0, $limit=FS_ITEM_LIMIT)
   {
      $linealist = array();
      
      $lineas = $this->db->select_limit("SELECT * FROM ".$this->table_name." WHERE referencia = ".$this->var2str($ref)." ORDER BY idpedido DESC", $limit, $offset);
      if( $lineas )
      {
         foreach($lineas as $l)
            $linealist[] = new linea_pedido_cliente($l);
      }
      
      return $linealist;
   }
   
   public function search($query='', $offset=0)
   {
      $linealist = array();
      $query = strtolower( $this->no_html($query) );
      
      $sql = "SELECT * FROM ".$this->table_name." WHERE ";
      if( is_numeric($query) )
      {
         $sql .= "referencia LIKE '%".$query."%' OR descripcion LIKE '%".$query."%'";
      }
      else
      {
         $buscar = str_replace(' ', '%', $query);
         $sql .= "lower(referencia) LIKE '%".$buscar."%' OR lower(descripcion) LIKE '%".$buscar."%'";
      }
      $sql .= " ORDER BY idpedido DESC, idlinea ASC";
      
      $lineas = $this->db->select_limit($sql, FS_ITEM_LIMIT, $offset);
      if( $lineas )
      {
         foreach($lineas as $l)
            $linealist[] = new linea_pedido_cliente($l);
      }
      
      return $linealist;
   }
   
   public function search_from_cliente2($codcliente, $ref='', $obs='', $offset=0)
   {
      $linealist = array();
      $ref = strtolower( $this->no_html($ref) );
      
      $sql = "SELECT * FROM ".$this->table_name." WHERE idpedido IN
         (SELECT idpedido FROM pedidoscli WHERE codcliente = ".$this->var2str($codcliente)."
         AND lower(observaciones) LIKE '".strtolower($obs)."%') AND ";
      if( is_numeric($ref) )
      {
         $sql .= "(referencia LIKE '%".$ref."%' OR descripcion LIKE '%".$ref."%')";
      }
      else
      {
         $buscar = str_replace(' ', '%', $ref);
         $sql .= "(lower(referencia) LIKE '%".$ref."%' OR lower(descripcion) LIKE '%".$ref."%')";
      }
      $sql .= " ORDER BY idpedido DESC, idlinea ASC";
      
      $lineas = $this->db->select_limit($sql, FS_ITEM_LIMIT, $offset);
      if( $lineas )
      {
         foreach($lineas as $l)
            $linealist[] = new linea_albaran_cliente($l);
      }
      return $linealist;
   }
}
