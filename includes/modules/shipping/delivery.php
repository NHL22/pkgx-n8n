<?php
/**
 * Giao hàng tận nơi
 * Phí cơ bản base_fee: 30.000đ | hoặc set lại trong cấu hình
 * Nếu giá trị đơn hàng >= 1 triệu free_money (set lại trong cấu hình) --> Phí = 0
 */
if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

$shipping_lang = ROOT_PATH.'languages/' .$GLOBALS['_CFG']['lang']. '/shipping/delivery.php';
if (file_exists($shipping_lang))
{
    global $_LANG;
    include_once($shipping_lang);
}

if (isset($set_modules) && $set_modules == TRUE)
{
    $i = (isset($modules)) ? count($modules) : 0;

    $modules[$i]['code']    = 'delivery';
    $modules[$i]['version'] = '1.0.0';
    $modules[$i]['desc']    = 'delivery_desc';
    $modules[$i]['cod']     = TRUE;

    $modules[$i]['author']  = 'ECSHOP Việt Nam';
    $modules[$i]['website'] = 'https://ecshopvietnam.com';

    $modules[$i]['configure'] = array(
                                   array('name' => 'base_fee',     'value'=>30000),
                                );

    $modules[$i]['print_model'] = 2;
    $modules[$i]['print_bg'] = '';
    $modules[$i]['config_lable'] = '';

    return;
}

class delivery
{

    var $configure;


    /**
     *
     *
     * @param: $configure[array]
     *
     * @return null
     */
    function __construct($cfg=array())
    {
        foreach ($cfg AS $key=>$val)
        {
            $this->configure[$val['name']] = $val['value'];
        }
    }

    /**
     *
     *
     * @param   float   $goods_weight
     * @param   float   $goods_amount
     * @return  decimal
     */
    function calculate($goods_weight, $goods_amount)
    {
        if ($this->configure['free_money'] > 0 && $goods_amount >= $this->configure['free_money'])
        {
            return 0;
        }
        else
        {
            return $this->configure['base_fee'];
        }
    }

    /**
     *
     *
     *
     * @access  public
     * @param   string  $invoice_sn
     * @return  string
     */
    function query($invoice_sn)
    {
        return $invoice_sn;
    }
}

?>
