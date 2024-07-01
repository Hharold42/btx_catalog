<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Корзина");

// /korzina/index.php

?>

<?php
$APPLICATION->IncludeComponent(
    "bitrix:sale.basket.basket",
    "",
    Array(
        "COLUMNS_LIST" => array("NAME", "DISCOUNT", "PRICE", "QUANTITY", "SUM"),
        "PATH_TO_ORDER" => "/zakaz/",
        "HIDE_COUPON" => "N",
        "QUANTITY_FLOAT" => "N",
        "PRICE_VAT_SHOW_VALUE" => "Y",
        "TEMPLATE_THEME" => "blue",
        "SET_TITLE" => "Y",
        "AJAX_MODE" => "N",
        "AJAX_OPTION_JUMP" => "N",
        "AJAX_OPTION_STYLE" => "Y",
        "AJAX_OPTION_HISTORY" => "N"
    )
);
?>

<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
?>
