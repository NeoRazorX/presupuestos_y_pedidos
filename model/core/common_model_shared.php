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
trait common_model_shared {
   
   /**
    * Código unificado del método "url" 
    * en modelos de presupuestos y pedidos
    * @string type $page
    * @mixed type $valor_id
    * @string type $param_id
    * @return string
    */
   private function url_shared($page, $valor_id, $param_id = 'id') {
      $result = 'index.php?page='.$page;
      if (is_null($valor_id)) {
         if ((substr($page, -1) == 'r') OR (substr($page, -1)) == 'n')
            $result .= 'es';
         else
            $result .= 's';        
      }
      else
         $result .= '&'.$param_id .'='.$valor_id;

      return $result;
   }

   /**
    * Código unificado del método "exists"
    * @param string $campo_id
    * @param mixed $valor_id
    * @return boolean
    */
   private function exists_shared($campo_id, $valor_id) {
      $result = FALSE;
      if (!is_null($valor_id))
         $result = $this->db->select("SELECT * FROM " . $this->table_name() . " WHERE " .$campo_id ." = " . strval($valor_id) . ";");
      return $result;
   }

   /**
    * Método unificado del método "all"
    * @param string $model
    * @param string $where
    * @param int    $offset
    * @param string $order
    * @param int    $limit
    * @return array
    */
   private function all_shared($model, $where, $offset, $order, $limit) {
      $sql = "SELECT * FROM " . $this->table_name();
      if ($where)
         $sql .= " WHERE " .$where;
      $sql .= " ORDER BY " . $order;
      
      $result = array();
      
      if ($limit == 0)
         $data = $this->db->select($sql);
      else
         $data = $this->db->select_limit($sql, $limit, $offset);
      
      if ($data) {
         foreach ($data as $record) {
            $result[] = new $model($record);
         }
      }

      return $result;
   }      
}
