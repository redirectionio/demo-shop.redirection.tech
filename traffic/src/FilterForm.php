<?php

namespace Traffic;

/**
 * The filters of a category page: a GET form, so applying them loads a new URL.
 */
final readonly class FilterForm
{
    /**
     * @param array<string, non-empty-list<string>> $choices    The values of each filter, like "color" => ["black", "blue"]
     * @param list<string>                          $sortOrders The sort orders, except the default one
     */
    public function __construct(
        public string $action,
        public array $choices,
        public array $sortOrders,
    ) {
    }

    /**
     * The URL of a few filters, picked like a visitor would.
     */
    public function pathOfRandomFilters(Random $random): string
    {
        $parameters = [];
        foreach ($random->sample(array_keys($this->choices), $random->chance(0.25) ? 2 : 1) as $name) {
            $parameters[] = [$name, $random->pick($this->choices[$name])];
        }
        if ([] !== $this->sortOrders && $random->chance(0.3)) {
            $parameters[] = ['sort', $random->pick($this->sortOrders)];
        }

        // http_build_query() cannot repeat a parameter, as browsers do for checkboxes.
        $query = implode('&', array_map(static fn (array $parameter): string => urlencode($parameter[0]) . '=' . urlencode($parameter[1]), $parameters));

        return '' === $query ? $this->action : "{$this->action}?{$query}";
    }
}
