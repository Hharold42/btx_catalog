<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Оформление заказа");

//  /zakaz/index.php

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["ORDER_CREATE"])) {
    CModule::IncludeModule("sale");
    CModule::IncludeModule("catalog");

    global $USER;
    $userId = $USER->GetID();
    $order = Bitrix\Sale\Order::create(SITE_ID, $userId);

    $order->setPersonTypeId(3); 

    $basket = Bitrix\Sale\Basket::loadItemsForFUser(Bitrix\Sale\Fuser::getId(), SITE_ID)->getOrderableItems();

    $order->setBasket($basket);

    $shipmentCollection = $order->getShipmentCollection();

    $shipment = $shipmentCollection->createItem();
    $service = Bitrix\Sale\Delivery\Services\Manager::getById(1);

    $shipment->setFields(array(
        'DELIVERY_ID' => $service['ID'],
        'DELIVERY_NAME' => $service['NAME'],
    ));

    $paymentCollection = $order->getPaymentCollection();
    $paymentService = Bitrix\Sale\PaySystem\Manager::getObjectById(1);
    $payment = $paymentCollection->createItem($paymentService);
    $payment->setField("SUM", $order->getPrice());
    $payment->setField("CURRENCY", $order->getCurrency());
    $propertyCollection = $order->getPropertyCollection();
    $propertyCollection->getItemByOrderPropertyId(20)->setValue("Тестовое имя");
    $propertyCollection->getItemByOrderPropertyId(26)->setValue("Тестовый адрес");

    $order->doFinalAction(true);
    $result = $order->save();

    if ($result->isSuccess()) {
        $orderId = $order->getId();
        echo "Заказ #" . $orderId . " успешно создан.";
        
        $payment->setPaid("Y");
        $payment->save();
        echo " Заказ помечен как оплаченный.";

        $event = new \Bitrix\Main\Event('sale', 'OnSaleOrderPaid', array('ENTITY' => $order));
        $event->send();
    } else {
        echo "Ошибка создания заказа: " . implode("<br>", $result->getErrorMessages());
    }
}
?>

<form method="post">
    <input type="submit" name="ORDER_CREATE" value="Оформить заказ">
</form>

<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
?>
