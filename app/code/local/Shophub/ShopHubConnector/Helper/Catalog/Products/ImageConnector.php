<?php

class Shophub_ShopHubConnector_Helper_Catalog_Products_ImageConnector extends Shophub_ShopHubConnector_Helper_Data
{

    /**
     * @param $product
     * @param null $images
     * @return bool|null
     */
    public function importImages($product, $images = null)
    {
        if (!$images) {
            return null;
        }
        $this->deleteAllProductImages($product);
        foreach ($images as $image) {
            $mainImage = $image['position'] == 0;
            $label = $image['name'];
            $this->importImage($product, $image['url'], $image['position'], $mainImage, $label);
        }
        $product->save();
        return true;
    }

    /**
     * @param $product
     */
    public function deleteAllProductImages($product)
    {
        Mage::app()->getStore()->setId(Mage_Core_Model_App::ADMIN_STORE_ID);
        $mediaApi = Mage::getModel("catalog/product_attribute_media_api");
        $items = $mediaApi->items($product->getId());
        $attributes = $product->getTypeInstance()->getSetAttributes();
        $gallery = $attributes['media_gallery'];
        foreach ($items as $item) {
            if ($gallery->getBackend()->getImage($product, $item['file'])) {
                $gallery->getBackend()->removeImage($product, $item['file']);
            }
        }
        $product->save();
    }

    /**
     * @param $product
     * @param $imageUrl
     * @param $imagePosition
     * @param bool $mainImage
     * @param string $imageLabel
     * @param bool $saveDirectly
     * @return bool|string
     */
    public function importImage($product, $imageUrl, $imagePosition, $mainImage = true, $imageLabel = "", $saveDirectly = false)
    {
        try {
            $imageFileSuffix = substr(strrchr($imageUrl, "."), 1);
            $filename = md5($imageUrl) . '.' . $imageFileSuffix;
            $filePath = Mage::getBaseDir('media') . DS . 'shophub_import' . DS . $filename;

            // if file exists, it has already been imported. Only update media gallery.
            if (!file_exists($filePath)) {
                $imageData = $this->getImageDataWithCurl($imageUrl);
                $writeResult = $this->writeImageDataToFilePath($imageData, $filePath);
                if (!$writeResult) {
                    return false;
                }
            }

            $this->addImageToProduct($product, $filePath, $imageLabel, $imagePosition, $mainImage);

            if ($saveDirectly) {
                $product->save();
            }
            return $filePath;
        } catch (Exception $e) {
            $this->logException($e);
        }
        return false;
    }

    /**
     * @param $imageUrl
     * @return mixed
     */
    private function getImageDataWithCurl($imageUrl)
    {
        $curl = curl_init($imageUrl);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($curl);
        curl_close($curl);
        return $result;
    }

    /**
     * @param $imageData
     * @param $filePath
     * @return int
     * @throws Exception
     */
    private function writeImageDataToFilePath($imageData, $filePath)
    {
        $file = fopen($filePath, 'x');
        if (!($file)) {
            throw new Exception("fopen in function importSingleImageToMageProduct with false result!");
        }
        $writeResult = fwrite($file, $imageData);
        fclose($file);
        if (!$writeResult) {
            throw new Exception("fwrite in function importSingleImageToMageProduct with false result. Image could not be written in directory media");
        }
        return $writeResult;
    }

    private function addImageToProduct($product, $filePath, $imageLabel = "", $imagePosition = 0, $mainImage = true)
    {
        Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
        if ($mainImage) {
            $mediaAttribute = array(
                'thumbnail',
                'small_image',
                'image'
            );
        } else {
            $mediaAttribute = NULL;
        }
        /**
         * Add image to media gallery
         *
         * @param string $file file path of image in file system
         * @param string|array $mediaAttribute code of attribute with type 'media_image',
         *                                         leave blank if image should be only in gallery
         * @param boolean $move if true, it will move source file
         * @param boolean $exclude mark image as disabled in product page view
         */
        $product->addImageToMediaGallery($filePath, $mediaAttribute, false, false);

        $gallery = $product->getData('media_gallery');
        $lastImage = array_pop($gallery['images']);
        $lastImage['label'] = $imageLabel;
        $lastImage['position'] = $imagePosition;
        array_push($gallery['images'], $lastImage);
        $product->setData('media_gallery', $gallery);
        return $product;
    }

    /**
     * @param $product
     */
    public function deleteAllEvenProductImages($product)
    {

        Mage::app()->getStore()->setId(Mage_Core_Model_App::ADMIN_STORE_ID);
        $mediaApi = Mage::getModel("catalog/product_attribute_media_api");
        $items = $mediaApi->items($product->getId());
        $attributes = $product->getTypeInstance()->getSetAttributes();
        $gallery = $attributes['media_gallery'];
        $counter = 0;
        foreach ($items as $item) {
            $counter++;
            if ($counter / 2 == round($counter / 2)) {
                if ($gallery->getBackend()->getImage($product, $item['file'])) {
                    $gallery->getBackend()->removeImage($product, $item['file']);
                }
            }
        }

    }

}