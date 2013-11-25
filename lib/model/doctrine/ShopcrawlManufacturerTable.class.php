<?php

/**
 * ShopcrawlManufacturerTable
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 */
class ShopcrawlManufacturerTable extends Doctrine_Table
{
    /**
     * Returns an instance of this class.
     *
     * @return object ShopcrawlManufacturerTable
     */
    public static function getInstance()
    {
        return Doctrine_Core::getTable('ShopcrawlManufacturer');
    }

    public function findOrCreateOneByName($name) {


        $manufacturer = $this->findOneByName($name);
        if (!$manufacturer) {
            $manufacturer = new ShopcrawlManufacturer();
            $manufacturer
                ->setName($name)
                ->save();
        }

        return $manufacturer;
    }
}