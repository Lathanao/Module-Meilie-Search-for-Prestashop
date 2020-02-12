<?php
/*******************************************************************
 *          2020 Lathanao - Module for Prestashop
 *          Add a great module and modules on your great shop.
 *
 *          @author         Lathanao <welcome@lathanao.com>
 *          @copyright      2020 Lathanao
 *          @version        1.0
 *          @license        MIT (see LICENCE file)
 ********************************************************************/

use PrestaShop\PrestaShop\Adapter\Image\ImageRetriever;
use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;
use PrestaShop\PrestaShop\Adapter\Product\ProductColorsRetriever;
use PrestaShop\PrestaShop\Core\Product\ProductListingPresenter;

include(dirname(__FILE__) . '/../../../config/config.inc.php');
include(dirname(__FILE__) . '/../../../init.php');

header('Content-Type: application/json; charset=utf-8');

ini_set('display_errors', 1);
ini_set('track_errors', 1);
ini_set('html_errors', 1);
ini_set('realpath_cache_size', '5M');
ini_set('max_execution_time', 60000);
error_reporting(E_ALL);

define('_COLLECTION_', 'products');

/* Check security token */
if (!Tools::isPHPCLI()) {
    if (Tools::substr(Tools::encrypt('ao_meili_search/cron'), 0, 10) !== Tools::getValue('token') || !Module::isInstalled('ao_meili_search')) {
        die('Bad token');
    }
}

$ao_meili_search = Module::getInstanceByName('ao_meili_search');

if (!$ao_meili_search->active) {
    die('Module Inactive');
}

if($resultImport = ao_product_collection_create()) {
    echo 'Type index : ' . json_decode($resultImport, true)['name'] . PHP_EOL;
    echo 'Uid index  : ' . json_decode($resultImport, true)['uid'] . PHP_EOL;
    Configuration::updateValue('SEARCH_UID_PRODUCT', json_decode($resultImport, true)['uid']);
}

if(ao_category_collection_import()) {
    die('Import product done in index ' . Configuration::get('SEARCH_UID_CATEGORY'));
} else {
    die('Import product fail');
}

function ao_product_collection_create () {

    if(!Configuration::get('SEARCH_API_URL')) {
        throw new \Exception('URL API server Meili must be set.');
    }

    if(!Configuration::get('SEARCH_API_PORT')) {
        throw new \Exception('Port API server Meili must be set.');
    }

    $uri = Configuration::get('SEARCH_API_URL') . '/indexes';
    $ao_meili_search = Module::getInstanceByName('ao_meili_search');
    $data = '{
          "name": "' . _COLLECTION_ . '",
          "schema": {
            "id": ["identifier", "indexed", "displayed"],
            "id_category_default": ["indexed", "displayed"],
            "id_shop_default": ["indexed", "displayed"],
            "manufacturer_name": ["indexed", "displayed"],
            "supplier_name": ["indexed", "displayed"],
            "name": ["indexed", "displayed"],
            "description": ["indexed", "displayed"],
            "description_short": ["indexed", "displayed"],
            "quantity": ["indexed", "displayed"],
            "price": ["indexed", "displayed"],
            "specificPrice": ["indexed", "displayed"],
            "on_sale": ["indexed", "displayed"],
            "online_only": ["indexed", "displayed"],
            "unity": ["indexed", "displayed"],
            "unit_price": ["indexed", "displayed"],
            "reference": ["indexed", "displayed"],
            "ean13": ["indexed", "displayed"],
            "isbn": ["indexed", "displayed"],
            "upc": ["indexed", "displayed"],
            "mpn": ["indexed", "displayed"],
            "link_rewrite": ["indexed", "displayed"],
            "meta_description": ["indexed", "displayed"],
            "meta_keywords": ["indexed", "displayed"],
            "meta_title": ["indexed", "displayed"],
            "quantity_discount": ["indexed", "displayed"],
            "customizable": ["indexed", "displayed"],
            "new": ["indexed", "displayed"],
            "active": ["indexed", "displayed"],
            "available_for_order": ["indexed", "displayed"],
            "category": ["indexed", "displayed"],
            "link": ["indexed", "displayed"],
            "link_image": ["indexed", "displayed"]
          }
        }';

    return $ao_meili_search->curlRequest($uri, $data);
}

function ao_category_collection_import() {

    $fieldsToKeep = array(
        'id',
        'id_category_default',
        'id_shop_default',
        'manufacturer_name',
        'supplier_name',
        'name',
        'description',
        'description_short',
        'quantity',
        'price',
        'specificPrice',
        'on_sale',
        'online_only',
        'unity',
        'unit_price',
        'reference',
        'ean13',
        'isbn',
        'upc',
        'link_rewrite',
        'meta_description',
        'meta_keywords',
        'meta_title',
        'quantity_discount',
        'customizable',
        'new',
        'active',
        'available_for_order',
        'category',
    );

    $ao_meili_search = Module::getInstanceByName('ao_meili_search');
    $url = Configuration::get('SEARCH_API_URL') . '/indexes/';
    $uri = $url. Configuration::get('SEARCH_UID_PRODUCT') . '/documents';
    $context = Context::getContext();
    $link = Context::getContext()->link;
    $idLang = Context::getContext()->language->id;
    $idShop = Context::getContext()->shop->id;
    $category_to_import = [];

    $assembler = new ProductAssembler($context);
    $presenterFactory = new ProductPresenterFactory($context);
    $presentationSettings = $presenterFactory->getPresentationSettings();
    $presenter = new ProductListingPresenter(
        new ImageRetriever(
            $context->link
        ),
        $context->link,
        new PriceFormatter(),
        new ProductColorsRetriever(),
        $context->getTranslator()
    );

    $products_for_template = [];

    foreach (getAllProductIds() as $key => &$rawProduct) {

        $product = $presenter->present(
            $presentationSettings,
            $assembler->assembleProduct($rawProduct),
            $context->language
        );

        $product['description'] = Tools::getDescriptionClean($product['description']);
        $product['description_short'] = Tools::getDescriptionClean($product['description_short']);

        $productToImport = [];
        foreach ($fieldsToKeep as $item) {
            $productToImport[$item] = $product[$item];
        }
        echo PHP_EOL . $uri;
        echo PHP_EOL . var_dump($productToImport);
        echo PHP_EOL;
        $ao_meili_search->curlRequest($uri, '[' . json_encode($productToImport) . ']');
    }

    return true;
}

function getAllProductIds($active = false, $root = false)
{
    $query = new DbQuery();
    $query->select('p.id_product');
    $query->from('product', 'p');
    $query->innerJoin('product_lang', 'pl', 'p.id_product = pl.id_product');
    $query->innerJoin('product_shop', 'ps', 'p.id_product = ps.id_product');
    if ($active) {
        $query->where('active = 1');
    }
    $query->where('pl.id_lang = ' . (int) 1);
    $query->where('pl.id_shop = ' . (int) 1);

    return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
}