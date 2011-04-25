<?php

namespace Nette\Forms\Controls;

use ArrayAccess,
	Nette\Forms\Container,
	Nette\Forms\Form,
	Nette\Utils\Html;

/**
 * Set of radio button controls.
 *
 * @author Mikuláš Dítě
 *
 * @property array $items
 * @property-read Nette\Utils\Html $separatorPrototype
 * @property-read Nette\Utils\Html $containerPrototype
 * @property-read Nette\Utils\Html $nodePrototype
 */
class RadioTree extends RadioList
{

	/** @var Nette\Utils\Html node */
	protected $node;



	/**
	 * @param string label
	 * @param array options from which to choose
	 */
	public function __construct($label = NULL, array $items = NULL)
	{
		parent::__construct($label, $items);
		$this->container = Html::el('ul');
		$this->node = Html::el('li');
	}



	/**
	 * Returns node HTML element template.
	 * @return Nette\Utils\Html
	 */
	final public function getNodePrototype()
	{
		return $this->node;
	}



	/**
	 * @param Form $form
	 * @param string $name
	 * @param string $label
	 * @return Nette\Forms\Controls\RadioTree provides fluent interface
	 */
	public static function addRadioTree(Form $form, $name, $label = NULL, array $items = NULL)
	{
		$form[$name] = new self($label, $items);
		return $form[$name];
	}



	public static function register()
	{
		Container::extensionMethod('addRadioTree', callback(__CLASS__, 'addRadioTree'));
	}



	/**
	 * Returns selected radio value.
	 * @param bool
	 * @return mixed
	 */
	public function getValue($raw = FALSE)
	{
		$keys = array();
		$iterator = new \RecursiveArrayIterator($this->items);
		$recursor = new RecursiveIteratorIteratorCallback($iterator);
		foreach ($recursor as $k => $val) {
			$keys[$k] = TRUE;
		}
		return is_scalar($this->value) && ($raw || isset($keys[$this->value])) ? $this->value : NULL;
	}



	/**
	 * Generates control's HTML element.
	 * @param  mixed
	 * @return Nette\Utils\Html
	 */
	public function getControl($key = NULL)
	{	
		if ($key === NULL) {
			$container = clone $this->container;
			$separator = (string) $this->separator;

		} elseif (!isset($this->items[$key])) {
			return NULL;
		}

		$control = BaseControl::getControl();
		$id = $control->id;
		$counter = -1;
		$value = $this->value === NULL ? NULL : (string) $this->getValue();
		$label = Html::el('label');

		$iterator = new \RecursiveArrayIterator($this->items);
		$recursor = new RecursiveIteratorIteratorCallback($iterator);
		$recursor->onBeginChildren[] = function() use($container) {
			$container->add($container->startTag());
		};
		$recursor->onEndChildren[] = function() use($container) {
			$container->add($container->endTag());
		};
		foreach ($recursor as $k => $val) {
			$counter++;
			if ($key !== NULL && $key != $k) continue; // intentionally ==

			$control->id = $label->for = $id . '-' . $counter;
			$control->checked = (string) $k === $value;
			$control->value = $k;

			if ($val instanceof Html) {
				$label->setHtml($val);
			} else {
				$label->setText($this->translate((string) $val));
			}

			if ($key !== NULL) {
				return (string) $control . (string) $label;
			}

			$node = clone $this->node;
			$container->add($node->add((string) $control . (string) $label . $separator));
			$control->data('nette-rules', NULL);
		}
		
		return $container;
	}
	
}



/**
 * @internal
 */
class RecursiveIteratorIteratorCallback extends \RecursiveIteratorIterator
{

	/** int */
	public $depth = 0;

	/** array of callbacks */
	public $onBeginChildren;

	/** array of callbacks */
	public $onEndChildren;



	public function __construct(\Traversable $iterator, $mode = \RecursiveIteratorIterator::LEAVES_ONLY, $flags = 0)
	{
		parent::__construct($iterator, $mode, $flags);
		$this->onBeginChildren = array();
		$this->onEndChildren = array();
	}



	public function beginChildren()
	{
		$this->depth++;
		foreach ($this->onBeginChildren as $callback) {
			callback($callback)->invoke($this);
		}
	}



	public function endChildren()
	{
		$this->depth--;
		foreach ($this->onEndChildren as $callback) {
			callback($callback)->invoke($this);
		}
	}

}
