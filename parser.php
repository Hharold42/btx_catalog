<?php

// /testParser/index.php

define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
define("BX_WITH_ON_AFTER_EPILOG", true);
define("NO_AGENT_CHECK", true);
define("DisableEventsCheck", true);

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

CModule::IncludeModule('iblock');
CModule::IncludeModule('catalog');

class ProductParser {
    private $apiUrl = "https://dummyjson.com/products";
    private $iblockId;

    public function __construct($iblockId) {
        $this->iblockId = $iblockId;
    }

    public function fetchProducts() {
        $response = file_get_contents($this->apiUrl);
        $data = json_decode($response, true);

        if (!empty($data['products'])) {
            foreach ($data['products'] as $product) {
                $this->addProductToIblock($product);
            }
            return "Products fetched and added successfully.";
        }
        return "No products found.";
    }

    private function addProductToIblock($product) {
        $categoryId = $this->getOrCreateSection($product['category'], 0);
        
        if (!empty($product['brand'])) {
            $brandId = $this->getOrCreateSection($product['brand'], $categoryId);
        } else {
            $brandId = $categoryId;
        }

        $el = new CIBlockElement;

        $fields = array(
            "IBLOCK_ID" => $this->iblockId,
            "IBLOCK_SECTION_ID" => $brandId,
            "NAME" => $product['title'],
            "DETAIL_PICTURE" => CFile::MakeFileArray($product['thumbnail']),
            "PREVIEW_PICTURE" => CFile::MakeFileArray($product['thumbnail']),
            "PROPERTY_VALUES" => array(
                "discountPercentage" => $product['discountPercentage'],
                "title" => $product['title'],
                "description" => $product['description'],
                "rating" => $product['rating'],
                "stock" => $product['stock']
            ),
            "ACTIVE" => "Y"
        );

        if ($PRODUCT_ID = $el->Add($fields)) {
            $catalogProduct = new CCatalogProduct();
            $catalogProduct->Add([
                'ID' => $PRODUCT_ID,
                'QUANTITY' => $product['stock']
            ]);

            $price = new CPrice();
            $price->Add([
                'PRODUCT_ID' => $PRODUCT_ID,
                'CATALOG_GROUP_ID' => 1,
                'PRICE' => $product['price'],
                'CURRENCY' => 'RUB'
            ]);

            echo "New ID: " . $PRODUCT_ID . "<br>";
        } else {
            echo "Error: " . $el->LAST_ERROR . "<br>";
        }
    }

    private function getOrCreateSection($name, $parentSectionId) {
        $sectionId = $this->getSectionByName($name, $parentSectionId);

        if (!$sectionId) {
            $bs = new CIBlockSection;
            $arFields = array(
                "ACTIVE" => "Y",
                "IBLOCK_ID" => $this->iblockId,
                "NAME" => $name,
                "IBLOCK_SECTION_ID" => $parentSectionId
            );

            $sectionId = $bs->Add($arFields);
        }

        return $sectionId;
    }

    private function getSectionByName($name, $parentSectionId) {
        $sectionId = false;
        $arFilter = array(
            'IBLOCK_ID' => $this->iblockId,
            'IBLOCK_SECTION_ID' => $parentSectionId,
            'NAME' => $name
        );

        $rsSections = CIBlockSection::GetList(array(), $arFilter);
        if ($arSection = $rsSections->Fetch()) {
            $sectionId = $arSection['ID'];
        }

        return $sectionId;
    }
}

$iblockId = 17;
$parser = new ProductParser($iblockId);
echo $parser->fetchProducts();
?>