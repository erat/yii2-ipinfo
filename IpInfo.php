<?php

/**
 * @copyright Copyright &copy; Kartik Visweswaran, Krajee.com, 2015
 * @package   yii2-ipinfo
 * @version   1.0.0
 */

namespace kartik\ipinfo;

use Yii;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use kartik\base\Widget;
use kartik\popover\PopoverX;

/**
 * IP Info widget for Yii2 with ability to display country flag and
 * geo position info. Uses the API from hostip.info to parse IP info.
 *
 * @see http://hostip.info
 *
 * @author Kartik Visweswaran <kartikv2@gmail.com>
 * @since 1.0
 */
class IpInfo extends Widget
{
    const API_HOME = 'http://www.hostip.info/';
    const API_INFO = 'http://api.hostip.info/get_json.php';
    const API_FLAG = 'http://api.hostip.info/flag.php';

    /**
     * @var string the ip address
     */
    public $ip;

    /**
     * @var bool whether to show flag
     */
    public $showFlag = true;

    /**
     * @var bool whether to display position coordinates
     */
    public $showPosition = true;

    /**
     * @var bool whether to show details in a popover on click of flag.
     * If set to false, the results will be rendered inline.
     */
    public $showPopover = true;

    /**
     * @var bool whether to display credits and link to hostip.info.
     */
    public $showCredits = true;

    /**
     * @var array the HTML attributes for the loading container. The following special tags are recognized:
     *      - `tag`: string, the `tag` in which the content will be rendered. Defaults to `div`.
     *      - `message`: string, the loading message to be shown. Defaults to `Fetching location info...`.
     */
    public $loadingOptions = ['class' => 'kv-ip-loading'];

    /**
     * @var array the HTML attributes for the credits. The following special tags are recognized:
     *      - `label`: string, the `label` for the credits link. Defaults to 'Revalidate IP info'.
     */
    public $creditsOptions = ['class' => 'btn btn-xs center-block', 'target' => '_blank'];

    /**
     * @var array the message to be shown when no data is found. Defaults to: `No data found for IP address {ip}`.
     */
    public $noData;

    /**
     * @var array the HTML attributes for the no data container. The following special tags are recognized:
     *      - `tag`: string, the `tag` in which the content will be rendered. Defaults to `div`.
     */
    public $noDataOptions = ['class' => 'alert alert-danger text-center'];

    /**
     * @var array the markup to be displayed when any exception is faced during processing by the API (e.g. no
     *     connectivity). You can set this to a blank string to not display anything. Defaults to:
     *      `<i class="glyphicon glyphicon-exclamation-sign text-danger"></i>`.
     */
    public $errorData = '<i class="glyphicon glyphicon-exclamation-sign text-danger"></i>';

    /**
     * @var array the HTML attributes for error data container. Defaults to: `['title' => 'IP fetch error']`. The
     *     following special tags are recognized:
     *     - `tag`: string, the `tag` in which the content will be rendered. Defaults to `span`.
     */
    public $errorDataOptions = ['class' => 'img-thumbnail btn-default', 'style' => 'padding:0 6px'];

    /**
     * @var array the list of column fields to be display as details. Each item in this array must correspond to the
     *     field `key` for each record in the JSON output. Note that the fields will be displayed in the same order as
     *     you set it here. If not set, the translated names are autogenerated (see [[_defaultFields]]).
     */
    public $fields = [];

    /**
     * @var array the widget configuration settings for `kartik\popover\PopoverX` widget that will show the details on
     *     hover.
     */
    public $popoverOptions = [];

    /**
     * @var array the HTML attributes for the flag image.
     */
    public $flagOptions = ['style' => 'height:18px'];

    /**
     * @var array the header title for content shown in the popover. Defaults to `IP Position Details`
     */
    public $contentHeader;

    /**
     * @var array the icon shown before the header title for content in the popover.
     */
    public $contentHeaderIcon = '<i class="glyphicon glyphicon-map-marker"></i> ';

    /**
     * @var array the HTML attributes for the ip info content table container.
     */
    public $contentOptions = ['class' => 'table'];

    /**
     * @var array the HTML attributes for the widget container. The following special tags are recognized:
     * - `tag`: string, the `tag` in which the content will be rendered. Defaults to `span`.
     */
    public $options = [];

    /**
     * @var array the default field keys and labels setting (@see `initOptions` method)
     */
    protected $_defaultFields = [];

    /**
     * @inheritdoc
     */
    public function run()
    {
        $this->initOptions();
        echo $this->renderWidget();
    }

    /**
     * Initialize widget options
     */
    protected function initOptions()
    {
        $this->_msgCat = 'kvip';
        $this->initI18N();
        $this->_defaultFields = [
            'country_code' => Yii::t('kvip', 'Country Code'),
            'country_name' => Yii::t('kvip', 'Country Name'),
            'city' => Yii::t('kvip', 'City'),
            'ip' => Yii::t('kvip', 'IP Address'),
            'lat' => Yii::t('kvip', 'Latitude'),
            'lng' => Yii::t('kvip', 'Longitude')
        ];
        if (!isset($this->errorDataOptions['title'])) {
            $this->errorDataOptions['title'] = Yii::t('kvip', 'IP fetch error');
        }
    }

    /**
     * Renders the widget
     *
     * @return string
     */
    protected function renderWidget()
    {
        $ip = $ipParam = '';
        $params = [];
        if (!empty($this->ip)) {
            $ip = Html::encode($this->ip);
            $ipParam = "?ip={$ip}";
            $params['ip'] = $ip;
        }
        if ($this->showPosition) {
            $params['position'] = true;
        }
        $loadData = ArrayHelper::remove($this->loadingOptions, 'message', Yii::t('kvip', 'Fetching location info...'));
        $content = self::renderTag(self::renderTag($loadData, $this->loadingOptions, 'div'), $this->options);
        if ($this->showFlag) {
            if (!isset($this->flagOptions['alt'])) {
                $this->flagOptions['alt'] = empty($ip) ? Yii::t('kvip', 'No Flag') : $ip;
            }
            $flag = Html::img(self::API_FLAG . $ipParam, $this->flagOptions);
            if ($this->showPopover) {
                $header = isset($this->contentHeader) ? $this->contentHeader : Yii::t('kvip', 'IP Position Details');
                $this->popoverOptions['header'] = $this->contentHeaderIcon . $header;
                if (!isset($this->popoverOptions['toggleButton']) && !isset($this->popoverOptions['toggleButton']['class'])) {
                    $this->popoverOptions['toggleButton']['class'] = 'btn btn-xs btn-link';
                }
                if (!isset($this->popoverOptions['toggleButton']['style'])) {
                    $this->popoverOptions['toggleButton']['style'] = 'margin:0';
                }
                $this->popoverOptions['toggleButton']['label'] = $flag;
                $this->popoverOptions['content'] = $content;
                $content = PopoverX::widget($this->popoverOptions);
            } else {
                $content = $flag . $content;
            }
        }
        $this->registerAssets($params);
        return $content;
    }

    /**
     * Register plugin assets. Uses `kvIpInfo` jQuery plugin created by Krajee to refresh the IP information.
     *
     * @param array $params
     */
    protected function registerAssets($params = [])
    {
        if (empty($this->noData)) {
            $noData = empty($this->ip) ? Yii::t('kvip', "No data found for the user's IP address.") :
                Yii::t('kvip', 'No data found for IP address {ip}.', ['ip' => '<kbd>' . $this->ip . '</kbd>']);
        } else {
            $noData = $this->noData;
        }
        $credits = '';
        if ($this->showCredits) {
            $label = ArrayHelper::remove($this->creditsOptions, 'label', Yii::t('kvip', 'Revalidate IP info'));
            $credits = Html::a($label, self::API_HOME, $this->creditsOptions);
        }
        $this->pluginOptions = [
            'fields' => empty($this->fields) ? array_keys($this->_defaultFields) : $this->fields,
            'defaultFields' => $this->_defaultFields,
            'url' => self::API_INFO,
            'params' => $params,
            'credits' => $credits,
            'contentOptions' => $this->contentOptions,
            'noData' => self::renderTag($noData, $this->noDataOptions, 'div'),
            'errorData' => empty($this->errorData) ? '' : self::renderTag($this->errorData, $this->errorDataOptions)
        ];
        $view = $this->getView();
        $this->registerPlugin('kvIpInfo');
        IpInfoAsset::register($view);
    }

    /**
     * Renders a tag based on content and options, in which  the tag is set within options.
     *
     * @param string $content the content to render
     * @param array  $options the HTML attributes for the content container
     * @param string $tag the default HTML tag to use if `$options['tag']` is not set.
     *
     * @return string
     */
    protected static function renderTag($content, &$options = [], $tag = 'span')
    {
        $tag = ArrayHelper::remove($options, 'tag', $tag);
        return Html::tag($tag, $content, $options);
    }
}
