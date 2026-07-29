<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$active_group = 'default';
$query_builder = TRUE;
//  'database' => 'service_management',
$db['default'] = array(
    'dsn'      => '',
    'hostname' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'warawat121_service_management',
    'dbdriver' => 'mysqli',
    'dbprefix' => '',
    'pconnect' => FALSE,
    'db_debug' => (ENVIRONMENT !== 'production'),
    'cache_on' => FALSE,
    'cachedir' => '',
    'char_set' => 'utf8mb4',
    'dbcollat' => 'utf8mb4_unicode_ci',
    'swap_pre' => '',
    'encrypt'  => FALSE,
    'compress' => FALSE,
    'stricton' => FALSE,
    'failover' => array(),
    'save_queries' => TRUE,
);

// เชื่อมไปฐานข้อมูลของเว็บ tgsmartlife (เว็บหลัก) แบบ read-only เพื่อดึงข้อมูล
// การลงทะเบียน/รับประกันสินค้า (ตาราง product_regis, product) มาแสดงในแผนที่
// credential ตรงกับ application/config/database.php ของระบบ tgsmartlife เอง
$db['tgsmartlife'] = array(
    'dsn'      => '',
    'hostname' => 'localhost',
    'username' => 'warawat121',
    'password' => 'b6AI1u45',
    'database' => 'warawat121_tgsmartlife',
    'dbdriver' => 'mysqli',
    'dbprefix' => '',
    'pconnect' => FALSE,
    'db_debug' => (ENVIRONMENT !== 'production'),
    'cache_on' => FALSE,
    'cachedir' => '',
    'char_set' => 'utf8',
    'dbcollat' => 'utf8_general_ci',
    'swap_pre' => '',
    'encrypt'  => FALSE,
    'compress' => FALSE,
    'stricton' => FALSE,
    'failover' => array(),
    'save_queries' => TRUE,
);
