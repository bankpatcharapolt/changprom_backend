<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$route['default_controller'] = 'Auth';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;

// Auth routes
$route['login'] = 'Auth/login';
$route['register'] = 'Auth/register';
$route['logout'] = 'Auth/logout';

// Dashboard routes
$route['dashboard'] = 'Dashboard/index';
$route['dashboard/calendar'] = 'Dashboard/calendar';
$route['dashboard/api_stats'] = 'Dashboard/api_stats';
$route['dashboard/api_technician_load'] = 'Dashboard/api_technician_load';
$route['dashboard/api_calendar_events'] = 'Dashboard/api_calendar_events';
$route['dashboard/api_assign'] = 'Dashboard/api_assign';
$route['dashboard/api_search_jobs'] = 'Dashboard/api_search_jobs';
$route['dashboard/api_technicians'] = 'Dashboard/api_technicians';
$route['dashboard/api_debug_phone'] = 'Dashboard/api_debug_phone';

// Service jobs routes
$route['service'] = 'Service/index';
$route['service/import'] = 'Service/import';
$route['service/import_excel'] = 'Service/import_excel';
$route['service/import_debug']  = 'Service/import_debug';

// REST API routes
$route['api/service']['GET']    = 'Api/get_services';
$route['api/service/(:num)']['GET'] = 'Api/get_service/$1';
$route['api/service']['POST']   = 'Api/create_service';
$route['api/service/(:num)']['PUT'] = 'Api/update_service/$1';
$route['api/service/(:num)']['DELETE'] = 'Api/delete_service/$1';
$route['api/service/datatable']['POST'] = 'Api/datatable';


// หน้าจัดการยานพาหนะ
$route['customer_map']                    = 'CustomerMap/index';
$route['customer_map/api_markers']        = 'CustomerMap/api_markers';
$route['customer_map/api_techs']          = 'CustomerMap/api_techs';
$route['customer_map/api_history']        = 'CustomerMap/api_history';
$route['map/api_history']                 = 'CustomerMap/api_history';
$route['customer_map/api_job_types']      = 'CustomerMap/api_job_types';
$route['map/api_job_types']              = 'CustomerMap/api_job_types';

// ── Public map (ไม่ต้อง login) ─────────────────────────────
$route['map']                             = 'CustomerMap/public_index';
$route['map/api_markers']                 = 'CustomerMap/api_markers';   // reuse เดิม
$route['map/api_techs']                   = 'CustomerMap/api_techs';     // reuse เดิม
$route['map/api_warranty_info']           = 'CustomerMap/api_warranty_info';
$route['map/api_job_detail/(:num)']       = 'CustomerMap/api_job_detail/$1';
$route['branch']                      = 'Branch/index';
$route['api/branch']                  = 'Branch/api_list';
$route['api/branch/active']           = 'Branch/api_active';
$route['api/branch/create']           = 'Branch/api_create';
$route['api/branch/get/(:num)']       = 'Branch/api_get/$1';
$route['api/branch/update/(:num)']    = 'Branch/api_update/$1';
$route['api/branch/delete/(:num)']    = 'Branch/api_delete/$1';
$route['vehicle']              = 'Vehicle/index';
$route['api/vehicle']          = 'Vehicle/api_list';
$route['api/vehicle/(:num)']   = 'Vehicle/api_get/$1';

// หน้าจัดการพนักงาน
$route['employee']                             = 'Employee/index';
$route['api/employee']                         = 'Employee/api_list';
$route['api/employee/datatable']               = 'Employee/api_datatable';
$route['api/employee/create']                  = 'Employee/api_create';
$route['api/employee/get/(:num)']              = 'Employee/api_get/$1';
$route['api/employee/update/(:num)']           = 'Employee/api_update/$1';
$route['api/employee/delete/(:num)']           = 'Employee/api_delete/$1';
$route['api/employee/request_access']          = 'Employee/api_request_access';
$route['api/employee/requests']                = 'Employee/api_requests';
$route['api/employee/requests/approve/(:num)'] = 'Employee/api_approve_request/$1';
$route['api/employee/requests/reject/(:num)']  = 'Employee/api_reject_request/$1';
$route['api/employee/grant/(:num)']            = 'Employee/api_grant/$1';
$route['api/employee/revoke/(:num)']           = 'Employee/api_revoke/$1';

// หน้าจัดการช่าง
$route['technician']                       = 'Technician/index';

// API ช่าง
$route['api/technician']                   = 'Technician/api_list';       // GET=list, POST=create (via _remap)
$route['api/technician/datatable']         = 'Technician/api_datatable';  // POST DataTables
$route['api/technician/(:num)']            = 'Technician/api_get/$1';     // GET/PUT/DELETE (via _remap)

// API ที่ calendar.js และ service.js ใช้อยู่แล้ว — ย้ายมาชี้ที่ Technician controller
$route['dashboard/api_technicians']        = 'Technician/api_all';        // GET active list (calendar.js)
$route['dashboard/api_technicians_search'] = 'Technician/api_search';     // GET ?q= autocomplete (service.js)
