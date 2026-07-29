<?php
defined('BASEPATH') OR exit('No direct script access allowed');
date_default_timezone_set('Asia/Bangkok');
$config['base_url'] = 'http://localhost/service_management/';
$config['index_page'] = '';
$config['uri_protocol'] = 'REQUEST_URI';
$config['url_suffix'] = '';
$config['language'] = 'english';
$config['charset'] = 'UTF-8';
$config['enable_hooks'] = FALSE;
$config['subclass_prefix'] = 'MY_';
$config['composer_autoload'] = FALSE;
$config['permitted_uri_chars'] = 'a-z 0-9~%.:_\-';
$config['allow_get_array'] = TRUE;
$config['enable_query_strings'] = FALSE;
$config['log_threshold'] = 1;
$config['log_path'] = '';
$config['log_file_extension'] = '';
$config['log_file_permissions'] = 0644;
$config['log_date_format'] = 'Y-m-d H:i:s';
$config['cache_path'] = '';
$config['error_views_path'] = '';
$config['cache_query_string'] = FALSE;
$config['encryption_key'] = 'YourSecretEncryptionKey1234567890Ab';

// ใช้เซ็นชื่อ/ตรวจ token สำหรับ embed แผนที่แบบไม่ต้อง login (จากหน้า
// tgsmartlife.com/register-product) — ต้องเป็นค่าเดียวกันกับ $config['map_token_secret']
// ในระบบ tgsmartlife (เว็บหลัก) ห้ามให้ค่าต่างกัน ไม่งั้น token ที่อีกฝั่งออกจะตรวจไม่ผ่าน
$config['map_token_secret'] = '9b5bed1b8011ae01c8638425d8bef98f97319e47d31b520c1fcaa43985cd08fb';

// Google Maps API Key
$config['gmaps_key'] = '';
$config['sess_driver'] = 'database';
$config['sess_cookie_name'] = 'ci_session';
$config['sess_expiration'] = 7200;
$config['sess_save_path'] = 'ci_sessions';
$config['sess_match_ip'] = FALSE;
$config['sess_time_to_update'] = 300;
$config['sess_regenerate_destroy'] = FALSE;
$config['cookie_prefix'] = '';
$config['cookie_domain'] = '';
$config['cookie_path'] = '/';
$config['cookie_secure'] = FALSE;
$config['cookie_httponly'] = FALSE;
$config['csrf_protection'] = FALSE;
$config['compress_output'] = FALSE;
$config['time_reference'] = 'local';
$config['rewrite_short_tags'] = FALSE;
$config['proxy_ips'] = '';
