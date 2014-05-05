<?php

/* 
 * Copyright 2014 Maverick
 * @author Maverick <deep.jyotsingh@gmail.com> 
 * @quote It's on me, always will be, always has been
 */

namespace maverick\cloneable;

use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;

/**
 * A Form Clonnable widget rendering attributes using the kartik\widgets\ActiveForm
 * It is basically used to generate the clonnable fields in a form using the jQuery and add more options
 * 
 * Basic Usage
 *
 * 
 * 
 * @author Maverick <deep.jyotsingh@gmail.com>
 * @version 1.0
 */

class CloneForm extends \kartik\builder\BaseForm
{
    // bootstrap grid column sizes
    const SIZE_LARGE = 'lg';
    const SIZE_MEDIUM = 'md';
    const SIZE_SMALL = 'sm';
    const SIZE_TINY = 'xs';

    // bootstrap maximum grid width
    const GRID_WIDTH = 12;

    /**
     * @var Model|ActiveRecord the model used for the form
     */
    public $model;

    /**
     * @var integer, the number of columns in which to split the fields horizontally. If not set, defaults to 1 column.
     */
    public $columns = 1;

    /**
     * @var string, the bootstrap device size for rendering each grid column. Defaults to `SIZE_SMALL`.
     */
    public $columnSize = self::SIZE_SMALL;

    /**
     * @var array the HTML attributes for the grid columns. Applicable only if `$columns` is greater than 1.
     */
    public $columnOptions = [];

    /**
     * @var array the HTML attributes for the rows. Applicable only if `$columns` is greater than 1.
     */
    public $rowOptions = [];

    /**
     * @var array the HTML attributes for the field/attributes container. The following options are additionally recognized:
     * - `tag`: the HTML tag for the container. Defaults to `fieldset`.
     */
    public $options = [];
    
    /**
     * @var integer maxCloneRows the maximum number of rows to clone
     */
    public $maxCloneRows = 0;
    
    /**
     * @var integer minCloneRows the minimum number of rows to clone
     */
    public $minCloneRows = 0;
    
    /**
     * @var integer startRow the number of rows at the start of the render
     */
    public $startRows = 1;
    
    /**
     * @var string Id of div in which the form would be rendered and clonned item would clone
     */
    public $rowName = '';
    
    /**
     * @var string GroupName of the Attributes or the field being rendered, to be used for tabular input model
     */
    public $rowGroupName = '';
    
    /**
     * @var string Add button label
     */
    public $addButtonLabel = 'Add';
    
    /**
     * @var string Remove button label
     */
    public $removeButtonLabel = 'Remove';
    
    /**
     * @var string fieldset tag
     */
    private $_tag;
    
    /**
     * @var string for div widget id
     */
    private $_widgetId;
    
    /**
     * 
     * @throws InvalidConfigException
     */    
    public function init() {
        parent::init();
        
        if(empty($this->model) || !$this->model instanceof yii\base\model)
        {
            throw new InvalidConfigException("The 'model' property must be set and must extend from '\\yii\\base\\model'.");
        }
        
        //if(isset($this->rowName))
        //{
        //    throw new InvalidConfigException("The 'rowName' must be set");
        //}
        
        //if($this->rowGroupName == '')
        //{
        //    throw new InvalidConfigException("The 'rowGroupName' must be set");
        //}
        
        if($this->minCloneRows > $this->startRows)
        {
            $this->startRows = $this->minCloneRows;
        }
        $this->initOptions();
        $this->registerAssets();
        echo Html::beginTag($this->_tag, $this->options);
    }
    
    /**
     * 
     */
    public function run()
    {
        echo $this->renderFieldSet();
        echo Html::endTag($this->_tag);
        parent::run();
    }
    
    /**
     * 
     */
    public function initOptions()
    {
        $this->_tag = ArrayHelper::remove($this->options, 'tag', 'fieldset');
        $this->_widgetId = $this->options['id'] = 'clonnable-field-widget-'.$this->getId();
        Html::addCssClass($this->options, 'clonnable-field-widget');
    }
    
    /**
     * 
     * @return type
     */
    protected function renderFieldSet()
    {
        $content = '';
        $cols = (is_int($this->columns) && $this->columns >= 1) ? $this->columns : 1;
        if($cols == 1)
        {
            $index = 0;
            foreach($this->attributes as $attribute=>$settings)
            {
                $content .= $this->parseInput($this->form, $this->model, $attribute, $settings, $index);
                $index++;
            }
            return $content;
        }
        
        $index = 0;
        $attrCount = count($this->attributes);
        $rows = (float)($attrCount / $cols);
        $rows = ceil($rows);
        $names = array_keys($this->attributes);
        $values = array_values($this->attributes);
        $width = (int)(self::GRID_WIDTH / $cols);
        Html::addCssClass($this->rowOptions, 'row');
        Html::addCssClass($this->rowOptions, $this->rowName);
        
        for($row = 1; $row <= $rows; $row++)
        {
            $content .= Html::beginTag('div', $this->rowOptions). "\n";
            for($col = 1; $col <= $cols; $col++)
            {
                if($index > $attrCount-1)
                {
                    break;
                }
                $attribute = $names[$index];
                $settings = $values[$index];
                $colOptions = ArrayHelper::getValue($settings, 'columnOptions', $this->columnOptions);
                Html::addCssClass($colOptions, 'col-' . $this->columnSize . '-'. $width);
                $content .= "\t" . Html::beginTag('div', $colOptions) . "\n";
                //$settings['options']['data-attribute'] = $names[$index];
                $content .= "\t\t" . $this->parseInput($this->form, $this->model, $attribute, $settings, $index) . "\n";
                $content .= "\t" . Html::endTag('div') . "\n";
                $index++;
            }
            $content .= Html::endTag('div');            
        }
        return $content;                    
    }
    
    /**
     * Parses input for `INPUT_RAW` type
     */
    protected function parseInput($form, $model, $attribute, $settings, $index)
    {
        $type = ArrayHelper::getValue($settings, 'type', self::INPUT_TEXT);
        if ($type === self::INPUT_RAW) 
        {
            return ($settings['value'] instanceof \Closure) ? call_user_func($settings['value'], $model, $index, $this) : $settings['value'];
        } 
        elseif($type == self::INPUT_WIDGET)
        {
            return static::renderInput($form, $model, $attribute, $settings);
        }
        else
        {
            $settings['options']['data-attribute'] = $attribute;
            $settings['options']['data-groupname'] = $this->rowGroupName;
            return static::renderInput($form, $model, $attribute, $settings);
        }
    }

    /**
     * Registers widget assets
     */
    protected function registerAssets()
    {
        $view = $this->getView();
        FormAsset::register($view);
    }
}
