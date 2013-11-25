<?php

class shopCrawlTestTask extends sfBaseTask
{
    private $_shopDir = 'data/shopcrawl/boarders/';
    private $_sitemapFile = 'data/shopcrawl/boarders/sitemap.crawl';


    public function configure()
    {
        $this->namespace = 'shopcrawl';
        $this->name = 'download';
    }

    public function execute($arguments = array(), $options = array())
    {
        $this->saveSitemap();

        $this->parseSitemap();

    }

    private function saveSitemap() {

        if (!file_exists($this->_sitemapFile)) {

            $this->getFilesystem()->mkdirs($this->_shopDir);
            $b = new sfWebBrowser();
            $b->get('http://www.boarders.de/s01.php?&pp=htm&htmnr=10');
            $res = $b->getResponseBody();
            $res = utf8_encode($res);
            //$this->log($res);
            $fp = fopen($this->_sitemapFile, 'w') or die;
            fwrite($fp, $res);
            fclose($fp);
            $this->log('sitemap downloaded');
        } else {
            $this->log('sitemap exist');
        }

    }

    private function parseSitemap() {

        $fp = fopen($this->_sitemapFile, 'r') or die;
        $contents = fread($fp, filesize($this->_sitemapFile));
        fclose($fp);

        //preg_match_all('/<span class=sm_aug>(.*?) &#040;[0-9]+&#041;<\/span>/si', $contents, $matches);
        preg_match_all('/id=ae_herst_tab_herst_([0-9]+)/si', $contents, $matches);

        $shopManufacturerIds = $matches[1];

        $databaseManager = new sfDatabaseManager($this->configuration);
        $manufacturerTable = ShopcrawlManufacturerTable::getInstance();

        foreach($shopManufacturerIds as $id) {

            preg_match('/<div id=ae_herst_tab_herst_'. $id . ' name=ae_herst_tab_herst_' . $id . '><table border=0>(.*?)<\/div><\/td><\/tr><\/table><\/div>/si', $contents, $matches);

            $manufacturerHtml = $matches[1];

            preg_match('/<span class=sm_herst>(.*?) &#040;[0-9]+&#041;<\/span>/si', $manufacturerHtml, $matches);

            $manufacturerName = $matches[1];

            $manufacturer =  $manufacturerTable->findOrCreateOneByName($manufacturerName);

            $manufacturerDir = $this->_shopDir . hash('md5',$manufacturerName) . '/';
            if(!is_dir($manufacturerDir)) {
                mkdir($manufacturerDir);
            }

            preg_match_all('/<a href="([a-zA-Z0-9_]+)\.html" ONMOUSEOVER=/si', $manufacturerHtml, $matches);

            $productUrls = $matches[1];

            foreach($productUrls as $url) {
                $fileName = $manufacturerDir . $url . '.crawl';
                if (!file_exists($fileName)) {
                    $b = new sfWebBrowser();
                    $b->get('http://www.boarders.de/' . $url . '.html');
                    $res = $b->getResponseBody();
                    $res = utf8_encode($res);
                    //$this->log($res);
                    $fp = fopen($fileName, 'w') or die;
                    fwrite($fp, $res);
                    fclose($fp);
                    $this->log($fileName . ' downloaded');
                }
                $this->parseProduct($fileName, $manufacturer);
            }

            //exit;
        }
        preg_match_all('/id=ae_aug_tab_([0-9]+)/si', $contents, $matches);



        foreach ($matches[1] as $val) {

            $this->log($val);
        }




    }

    private function parseProduct($file, $manufacturer){


        if(file_exists($file)) {

            $databaseManager = new sfDatabaseManager($this->configuration);
            $categoryTable = ShopcrawlCategoryTable::getInstance();

            $fp = fopen($file, 'r') or die;
            $contents = fread($fp, filesize($file));
            fclose($fp);

            preg_match('/<span style="color: #164e78; font-size: 24px; font-family: Arial;">(.*?)<\/span>/si', $contents, $match);

            $name = trim($match[1]);

            preg_match('/Artikelnummer:<\/span>(.*?)<\/span>/si', $contents, $match);

            $sku = trim ( strip_tags ( $match[1] ) );


            preg_match('/Geschlecht:<\/span>(.*?)<\/span>/si', $contents, $match);

            $gender = trim ( strip_tags ( $match[1] ) );

            preg_match('/Farbe:<\/span>(.*?)<\/span>/si', $contents, $match);

            $color = trim ( strip_tags ( $match[1] ) );

            preg_match('/Material:<\/span>(.*?)<\/span>/si', $contents, $match);

            $material = trim ( strip_tags ( $match[1] ) );

            preg_match('/class=aa_price2>(.*?)<\/span>/si', $contents, $match);

            $price = trim ( strip_tags ( $match[1] ) );


            preg_match('/<div class=ada_text>(.*?)<\/div>/si', $contents, $match);

            if ( $match[1] != '--') {
                $description = trim ( strip_tags ( $match[1] ) );
            } else {
                $description = '';
            }

            preg_match('/property="v:title">(.*?)<\/a>/si', $contents, $match);

            $categoryName = trim ( $match[1] );

            $categoryName = ($categoryName != '' ) ? $categoryName : 'undefined';

            $category = $categoryTable->findOrCreateOneByName($categoryName);

            $productTable = ShopcrawlProductTable::getInstance();
            if (!$productTable->findOneBySku($sku)){
                $product = new ShopcrawlProduct();
                $product
                    ->setShopcrawlCategory($category)
                    ->setShopcrawlManufacturer($manufacturer)
                    ->setName($name)
                    ->setSku($sku)
                    ->setGender($gender)
                    ->setColor($color)
                    ->setPrice($price)
                    ->setMaterial($material)
                    ->setDescription($description)
                    ->setShopId(1)
                    ->save();
            }

        }

    }
}