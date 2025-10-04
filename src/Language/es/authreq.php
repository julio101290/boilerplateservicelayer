<?php

$authreq["logDescription"] = "La autorizacion fue guardada con los siguientes datos:";




$authreq["title"] = "Autorización de requisiciones";
$authreq["subtitle"] = "Autoriza las requisiciones en el SAP";
$authreq["fields"]["folio"] = "Folio";
$authreq["fields"]["warehouse"] = "Almacen";
$authreq["fields"]["date"] = "Fecha";
$authreq["fields"]["nameAuth"] = "Autorizador";

$authreq["fields"]["actions"] = "Acciones";
$authreq["msg"]["msg_insert"] = "El user_sap_link ha sido agregado correctamente.";
$authreq["msg"]["msg_update"] = "El user_sap_link ha sido modificado correctamente.";
$authreq["msg"]["msg_delete"] = "El user_sap_link ha sido eliminado correctamente.";
$authreq["msg"]["msg_get"] = "El enlace de usuario SAP ha sido obtenido exitosamente.";
$authreq["msg"]["msg_get_fail"] = "El enlace de usuario SAP no se encontró o ya fue eliminado.";

return $authreq;