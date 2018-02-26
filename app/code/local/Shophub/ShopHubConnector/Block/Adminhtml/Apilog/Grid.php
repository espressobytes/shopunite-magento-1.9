<?php

class Shophub_ShopHubConnector_Block_Adminhtml_Apilog_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('apiLog_grid'); // primary key of the database
        $this->setDefaultSort('id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }

    /**
     * @return string
     */
    protected function _getCollectionClass()
    {
        return 'shophubconnector/apiLog'; // model for the grid
    }

    /**
     * @return Mage_Adminhtml_Block_Widget_Grid
     */
    protected function _prepareCollection()
    {
        $collection = Mage::getModel('shophubconnector/apiLog')->getCollection();;
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * @return $this
     * @throws Exception
     */
    protected function _prepareColumns()
    {

        $this->addColumn('id', array(
            'header'    => Mage::helper('shophubconnector')->__('ID'),
            'align'     =>'right',
            'width'     => '50px',
            'index'     => 'id',
        ));

        $this->addColumn('date_time', array(
            'header'    => Mage::helper('shophubconnector')->__('Date/Time'),
            'align'     => 'left',
            'width'     => '120px',
            'type'      => 'date_time',
            'index'     => 'date_time',
        ));

        $this->addColumn('method', array(
            'header'    => Mage::helper('shophubconnector')->__('Method'),
            'width'     =>'200px',
            'index'     => 'method',
        ));

        $this->addColumn('route', array(
            'header'    => Mage::helper('shophubconnector')->__('Route'),
            'index'     => 'route',
        ));

        $this->addColumn('response_status_code', array(
            'header'    => Mage::helper('shophubconnector')->__('Response Status Code'),
            'index'     => 'response_status_code',
        ));

        $this->addColumn('response_content', array(
            'header'    => Mage::helper('shophubconnector')->__('Response Content'),
            'index'     => 'response_content',
        ));

        $this->addColumn('error_message', array(
            'header'    => Mage::helper('shophubconnector')->__('Error Message'),
            'index'     => 'error_message',
        ));

        return parent::_prepareColumns();
    }

    /**
     * @param $row
     * @return string
     */
    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/show', array('id' => $row->getId()));
    }

    /**
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current'=>true));
    }

}