<?php
use Bitrix\Main\EventManager;
use Bitrix\Sale\Order;
use Bitrix\Main\Mail\Event;

// /bitrix/php_interface/include/event_handler.php

EventManager::getInstance()->addEventHandler('sale', 'OnSaleOrderPaid', 'OnOrderPaidHandler');

function OnOrderPaidHandler(Bitrix\Main\Event $event)
{
    $order = $event->getParameter("ENTITY");
    if (!$order->isPaid()) {
        return;
    }

    $userId = $order->getUserId();
    $user = \CUser::GetByID($userId)->Fetch();
    $login = $user['LOGIN'];

    $bonusSum = 0;
    foreach ($order->getBasket()->getBasketItems() as $item) {
        
        $price = $item->getPrice();
        $quantity = $item->getQuantity();
        $DP;

        $elementId = $item->getField("PRODUCT_ID"); 
        $propertyCode = "discountPercentage";
    
        $res = CIBlockElement::GetProperty(
            17,
            $elementId,
            array("sort" => "asc"),
            array("CODE" => $propertyCode)
        );
    
        if ($ob = $res->GetNext()) {
            $DP = $ob['VALUE'];
        } else {
            echo "Property not found.";
        }
    
        $bonusSum += $price * $quantity * $DP / 100;
    }

    $userBonus = $user['UF_BONUS'] + $bonusSum;
    $userUpdate = new \CUser;
    $userUpdate->Update($userId, ['UF_BONUS' => $userBonus]);

    $adminEmail = 'torbenborstl@gmail.com';
    $mailFields = [
        'EMAIL' => $adminEmail,
        'LOGIN' => $login,
        'BONUS_SUM' => $bonusSum,
    ];
    // \CEvent::Send('SALE_ORDER_PAID', 's1', $mailFields);
    \Bitrix\Main\Mail\Event::send([    
        "EVENT_NAME" => "SALE_ORDER_PAID",
        'MESSAGE_ID' => 62,
        "LID" => "s1",
        "C_FIELDS" => [
            'EMAIL' => $adminEmail,
            'LOGIN' => $login,
            'BONUS_SUM' => $bonusSum,
        ]
    ]);
}
?>
