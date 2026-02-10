<?php

namespace Filament\Forms\Components;

use Closure;
use Filament\SpatieLaravelTagsPlugin\Types\AllTagTypes;
use Filament\Support\Components\Attributes\ExposedLivewireMethod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Renderless;
use Spatie\Tags\Tag;

class SpatieTagsInput extends TagsInput
{
    protected string $view = 'spatie-tags-plugin::components.spatie-tags-input';

    protected string | Closure | AllTagTypes | null $type;

    protected bool | Closure $isSearchable = false;

    protected ?Closure $getSearchResultsUsing = null;

    protected int | Closure $searchDebounce = 300;

    protected int | Closure $minSearchLength = 2;

    protected int | Closure $searchResultsLimit = 20;

    protected string | Closure | null $noSearchResultsMessage = null;

    protected string | Closure | null $searchingMessage = null;

    protected string | Closure | null $searchPromptMessage = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->type(new AllTagTypes);

        $this->loadStateFromRelationshipsUsing(static function (SpatieTagsInput $component, ?Model $record): void {
            if (! method_exists($record, 'tagsWithType')) {
                return;
            }

            $type = $component->getType();
            $record->load('tags');

            if ($component->isAnyTagTypeAllowed()) {
                $tags = $record->getRelationValue('tags');
            } else {
                $tags = $record->tagsWithType($type);
            }

            $component->state($tags->pluck('name')->all());
        });

        $this->saveRelationshipsUsing(static function (SpatieTagsInput $component, ?Model $record, array $state): void {
            if (! (method_exists($record, 'syncTagsWithType') && method_exists($record, 'syncTags'))) {
                return;
            }

            if (
                ($type = $component->getType()) &&
                (! $component->isAnyTagTypeAllowed())
            ) {
                $record->syncTagsWithType($state, $type);
                $record->unsetRelation('tags');

                return;
            }

            $component->syncTagsWithAnyType($record, $state);
            $record->unsetRelation('tags');
        });

        $this->dehydrated(false);
    }

    /**
     * Syncs tags with the record without taking types into account. This avoids recreating existing tags with an empty type.
     * Spatie's `HasTags` trait does not have functionality for this behavior.
     *
     * @param  array<string>  $state
     */
    protected function syncTagsWithAnyType(?Model $record, array $state): void
    {
        if (! ($record && method_exists($record, 'tags'))) {
            return;
        }

        $tagClassName = config('tags.tag_model', Tag::class);

        $tags = collect($state)->map(function (string $tagName) use ($tagClassName) { /** @phpstan-ignore argument.templateType */
            $locale = $tagClassName::getLocale();

            $tag = $tagClassName::findFromStringOfAnyType($tagName, $locale);

            if ($tag?->isEmpty() ?? true) {
                $tag = $tagClassName::create([
                    'name' => [$locale => $tagName],
                ]);
            }

            return $tag;
        })->flatten();

        $record->tags()->sync($tags->pluck('id'));
    }

    public function type(string | Closure | AllTagTypes | null $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function searchable(bool | Closure $condition = true): static
    {
        $this->isSearchable = $condition;

        return $this;
    }

    public function getSearchResultsUsing(?Closure $callback): static
    {
        $this->getSearchResultsUsing = $callback;

        return $this;
    }

    public function searchDebounce(int | Closure $debounce): static
    {
        $this->searchDebounce = $debounce;

        return $this;
    }

    public function minSearchLength(int | Closure $length): static
    {
        $this->minSearchLength = $length;

        return $this;
    }

    public function searchResultsLimit(int | Closure $limit): static
    {
        $this->searchResultsLimit = $limit;

        return $this;
    }

    public function noSearchResultsMessage(string | Closure | null $message): static
    {
        $this->noSearchResultsMessage = $message;

        return $this;
    }

    public function searchingMessage(string | Closure | null $message): static
    {
        $this->searchingMessage = $message;

        return $this;
    }

    public function searchPromptMessage(string | Closure | null $message): static
    {
        $this->searchPromptMessage = $message;

        return $this;
    }

    public function isSearchable(): bool
    {
        return (bool) $this->evaluate($this->isSearchable);
    }

    public function getSearchDebounce(): int
    {
        return (int) $this->evaluate($this->searchDebounce);
    }

    public function getMinSearchLength(): int
    {
        return (int) $this->evaluate($this->minSearchLength);
    }

    public function getSearchResultsLimit(): int
    {
        return (int) $this->evaluate($this->searchResultsLimit);
    }

    public function getNoSearchResultsMessage(): string
    {
        return $this->evaluate($this->noSearchResultsMessage) ?? __('No results found.');
    }

    public function getSearchingMessage(): string
    {
        return $this->evaluate($this->searchingMessage) ?? __('Searching...');
    }

    public function getSearchPromptMessage(): string
    {
        return $this->evaluate($this->searchPromptMessage) ?? __('Type at least :min characters...', ['min' => $this->getMinSearchLength()]);
    }

    /**
     * @return array<string>
     */
    #[ExposedLivewireMethod]
    #[Renderless]
    public function getSearchResultsForJs(string $search): array
    {
        if ($this->getSearchResultsUsing) {
            return $this->evaluate($this->getSearchResultsUsing, [
                'search' => $search,
                'query' => $search,
            ]);
        }

        return $this->getDefaultSearchResults($search);
    }

    /**
     * @return array<string>
     */
    protected function getDefaultSearchResults(string $search): array
    {
        $tagClass = config('tags.tag_model', Tag::class);

        try {
            $model = $this->getModel();

            if ($model && method_exists($model, 'getTagClassName')) {
                $tagClass = $model::getTagClassName();
            }
        } catch (\Throwable) {
            // Container may not be initialized in unit tests
        }

        $type = $this->getType();
        $limit = $this->getSearchResultsLimit();

        $query = $tagClass::query()->containing($search);

        if (! $this->isAnyTagTypeAllowed()) {
            $query->when(
                filled($type),
                fn (Builder $query) => $query->where('type', $type),
                fn (Builder $query) => $query->where('type', null),
            );
        }

        return $query->limit($limit)->pluck('name')->all();
    }

    public function getSuggestions(): array
    {
        if ($this->isSearchable()) {
            return [];
        }

        if ($this->suggestions !== null) {
            return parent::getSuggestions();
        }

        $tagClass = config('tags.tag_model', Tag::class);

        try {
            $model = $this->getModel();

            if ($model && method_exists($model, 'getTagClassName')) {
                $tagClass = $model::getTagClassName();
            }
        } catch (\Throwable) {
            // Container may not be initialized in unit tests
        }

        $type = $this->getType();
        $query = $tagClass::query();

        if (! $this->isAnyTagTypeAllowed()) {
            $query->when(
                filled($type),
                fn (Builder $query) => $query->where('type', $type),
                fn (Builder $query) => $query->where('type', null),
            );
        }

        return $query->pluck('name')->all();
    }

    public function getType(): string | AllTagTypes | null
    {
        return $this->evaluate($this->type);
    }

    public function isAnyTagTypeAllowed(): bool
    {
        return $this->getType() instanceof AllTagTypes;
    }
}
