<?php

namespace Spatie\Menu;

use Countable;
use Spatie\HtmlElement\HtmlElement;
use Spatie\Menu\Helpers\Arr;
use Spatie\Menu\Helpers\Reflection;
use Spatie\Menu\Helpers\Url;
use Spatie\Menu\Traits\HtmlAttributes;
use Spatie\Menu\Traits\ParentAttributes;

class Menu implements Item, Countable
{
    use HtmlAttributes, ParentAttributes;

    /** @var array */
    protected $items = [];

    /** @var string */
    protected $prepend = '';

    /** @var string */
    protected $append = '';

    /** @var array */
    protected $filters = [];

    /** @var string */
    protected $activeClass = 'active';

    /**
     * @param \Spatie\Menu\Item[] ...$items
     */
    protected function __construct(Item ...$items)
    {
        $this->items = $items;

        $this->initializeHtmlAttributes();
        $this->initializeParentAttributes();
    }

    /**
     * Create a new menu, optionally prefilled with items.
     *
     * @param array $items
     *
     * @return static
     */
    public static function new(array $items = [])
    {
        return new static(...array_values($items));
    }

    /**
     * Add an item to the menu. This also applies all registered filters to the
     * item.
     *
     * @param \Spatie\Menu\Item $item
     *
     * @return $this
     */
    public function add(Item $item)
    {
        foreach ($this->filters as $filter) {
            $this->applyFilter($filter, $item);
        }

        $this->items[] = $item;

        return $this;
    }

    /**
     * Add an item to the menu if a (non-strict) condition is met.
     *
     * @param bool              $condition
     * @param \Spatie\Menu\Item $item
     *
     * @return $this
     */
    public function addIf($condition, Item $item)
    {
        if ($condition) {
            $this->add($item);
        }

        return $this;
    }

    /**
     * Shortcut function to add a plain link to the menu.
     *
     * @param string $url
     * @param string $text
     *
     * @return $this
     */
    public function link(string $url, string $text)
    {
        return $this->add(Link::to($url, $text));
    }

    /**
     * Add a link to the menu if a (non-strict) condition is met.
     *
     * @param bool   $condition
     * @param string $url
     * @param string $text
     *
     * @return $this
     */
    public function linkIf($condition, string $url, string $text)
    {
        if ($condition) {
            $this->link($url, $text);
        }

        return $this;
    }

    /**
     * Shortcut function to add raw html to the menu.
     *
     * @param string $html
     *
     * @return $this
     */
    public function html(string $html)
    {
        return $this->add(Html::raw($html));
    }

    /**
     * Add a chunk of html if a (non-strict) condition is met.
     *
     * @param bool   $condition
     * @param string $html
     *
     * @return $this
     */
    public function htmlIf($condition, string $html)
    {
        if ($condition) {
            $this->html($html);
        }

        return $this;
    }

    /**
     * Iterate over all the items and apply a callback. If you typehint the
     * item parameter in the callable, it wil only be applied to items of that
     * type.
     *
     * @param callable $callable
     *
     * @return $this
     */
    public function each(callable $callable)
    {
        $type = Reflection::firstParameterType($callable);

        foreach ($this->items as $item) {
            if (!Reflection::itemMatchesType($item, $type)) {
                continue;
            }

            $callable($item);
        }

        return $this;
    }

    /**
     * Register a filter to the menu. When an item is added, all filters will be
     * applied to the item. If you typehint the item parameter in the callable, it
     * will only be applied to items of that type.
     *
     * @param callable $callable
     *
     * @return $this
     */
    public function registerFilter(callable $callable)
    {
        $this->filters[] = $callable;

        return $this;
    }

    /**
     * Apply a filter to an item. Returns the result of the filter.
     *
     * @param callable          $filter
     * @param \Spatie\Menu\Item $item
     */
    protected function applyFilter(callable $filter, Item $item)
    {
        $type = Reflection::firstParameterType($filter);

        if (!Reflection::itemMatchesType($item, $type)) {
            return;
        }

        $filter($item);
    }

    /**
     * Apply a callable to all existing items, and register it as a filter so it
     * will get applied to all new items too. If you typehint the item parameter
     * in the callable, it wil only be applied to items of that type.
     *
     * @param callable $callable
     *
     * @return $this
     */
    public function applyToAll(callable $callable)
    {
        $this->each($callable);
        $this->registerFilter($callable);

        return $this;
    }

    /**
     * Prefix all the links in the menu.
     *
     * @param string $prefix
     *
     * @return $this
     */
    public function prefixLinks(string $prefix)
    {
        return $this->applyToAll(function (Link $link) use ($prefix) {
            $link->prefix($prefix);
        });
    }

    /**
     * Prepend the menu with a string of html on render.
     *
     * @param string $prepend
     *
     * @return $this
     */
    public function prepend(string $prepend)
    {
        $this->prepend = $prepend;

        return $this;
    }

    /**
     * Prepend the menu with a string of html on render if a certain condition is
     * met.
     *
     * @param bool   $condition
     * @param string $prepend
     *
     * @return $this
     */
    public function prependIf(bool $condition, string $prepend)
    {
        if ($condition) {
            return $this->prepend($prepend);
        }

        return $this;
    }

    /**
     * Append a string of html to the menu on render.
     *
     * @param string $append
     *
     * @return $this
     */
    public function append(string $append)
    {
        $this->append = $append;

        return $this;
    }

    /**
     * Append the menu with a string of html on render if a certain condition is
     * met.
     *
     * @param bool   $condition
     * @param string $append
     *
     * @return static
     */
    public function appendIf(bool $condition, string $append)
    {
        if ($condition) {
            return $this->append($append);
        }

        return $this;
    }

    /**
     * Determine whether the menu is active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        foreach ($this->items as $item) {
            if ($item->isActive()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Set multiple items in the menu as active based on a callable that filters
     * through items. If you typehint the item parameter in the callable, it will
     * only be applied to items of that type.
     *
     * @param callable|string $urlOrCallable
     * @param string          $root
     *
     * @return $this
     */
    public function setActive($urlOrCallable, string $root = '/')
    {
        if (is_string($urlOrCallable)) {
            return $this->setActiveFromUrl($urlOrCallable, $root);
        }

        if (is_callable($urlOrCallable)) {
            return $this->setActiveFromCallable($urlOrCallable);
        }

        throw new \InvalidArgumentException('`setActive` requires a pattern or a callable');
    }

    /**
     * Set all relevant children active based on the current request's URL.
     *
     * /, /about, /contact => request to /about will set the about link active.
     *
     * /en, /en/about, /en/contact => request to /en won't set /en active if the
     *                                request root is set to /en.
     *
     * @param string $url  The current request url.
     * @param string $root If the link's URL is an exact match with the request
     *                     root, the link won't be set active. This behavior is
     *                     to avoid having home links active on every request.
     *
     * @return $this
     */
    public function setActiveFromUrl(string $url, string $root = '/')
    {
        $this->applyToAll(function (Menu $menu) use ($url, $root) {
            $menu->setActiveFromUrl($url, $root);
        });

        $requestUrl = Url::parts($url);
        $requestRoot = Url::stripTrailingSlashes($root, '/');

        $this->applyToAll(function ($item) use ($requestUrl, $requestRoot) {

            // Not using a magic typehint since we need to do two instance checks
            if (!$item instanceof HasUrl || !$item instanceof Activatable) {
                return;
            }

            $url = Url::parts($item->getUrl());

            // If the menu item is on a different host it can't be active.
            if ($url['host'] !== '' && $url['host'] !== $requestUrl['host']) {
                return;
            }

            // If the request url or the link url is on the root, only set exact matches active.
            if (
                $requestUrl['path'] === $requestRoot ||
                $url['path'] === $requestRoot
            ) {
                if ($url['path'] === $requestUrl['path']) {
                    $item->setActive();
                }

                return;
            }

            // If the request path is empty and it isn't the root, there's most likely a
            // configuration error, and the item isn't active.
            if (empty($url['path'])) {
                return;
            }

            // The menu item is active if it's path starts with the request path.
            if (strpos($requestUrl['path'], $url['path']) === 0) {
                $item->setActive();
            };
        });

        return $this;
    }

    /**
     * @param callable $callable
     *
     * @return $this
     */
    public function setActiveFromCallable(callable $callable)
    {
        $this->applyToAll(function (Menu $menu) use ($callable) {
            $menu->setActiveFromCallable($callable);
        });

        $type = Reflection::firstParameterType($callable);

        $this->applyToAll(function (Activatable $item) use ($callable, $type) {

            if (!Reflection::itemMatchesType($item, $type)) {
                return;
            }

            if ($callable($item)) {
                $item->setActive();
            }
        });

        return $this;
    }

    /**
     * Set the class name that will be used on active items for this menu.
     *
     * @param string $class
     *
     * @return $this
     */
    public function setActiveClass(string $class)
    {
        $this->activeClass = $class;

        return $this;
    }

    /**
     * Render the menu.
     *
     * @return string
     */
    public function render(): string
    {
        $contents = HtmlElement::render(
            'ul',
            $this->htmlAttributes->toArray(),
            Arr::map($this->items, function (Item $item) {
                return HtmlElement::render(
                    $item->isActive() ? "li.{$this->activeClass}" : 'li',
                    $item->getParentAttributes(),
                    $item->render()
                );
            })
        );

        return "{$this->prepend}{$contents}{$this->append}";
    }

    /**
     * The amount of items in the menu.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->render();
    }
}
