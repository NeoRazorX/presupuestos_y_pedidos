<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2014-2015  Carlos Garcia Gomez  neorazorx@gmail.com
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

require_model('pedido_cliente.php');
require_model('presupuesto_cliente.php');

class presupuestos_pedidos_cron
{
   public function __construct(&$db)
   {
      /// forzamos la comprobación de modelos
      $ped = new pedido_cliente();
      $pre = new presupuesto_cliente();
      
      /// marcamos como rechazados todos los presupuestos con finoferta ya pasada
      $db->exec("UPDATE presupuestoscli SET status = '2' WHERE finoferta < ".$pre->var2str(Date('d-m-Y'))." AND idpedido is null;");

      /// marcamos como pendientes los pedidos sin idpedido
      $db->exec("UPDATE pedidoscli SET status = '0' WHERE status = '1' AND idalbaran is null;");
   }
}

new presupuestos_pedidos_cron($db);