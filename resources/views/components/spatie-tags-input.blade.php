@php
    $fieldWrapperView = $getFieldWrapperView();
    $extraAttributes = $getExtraAttributes();
    $extraInputAttributeBag = $getExtraInputAttributeBag();
    $color = $getColor() ?? 'primary';
    $id = $getId();
    $isAutofocused = $isAutofocused();
    $isDisabled = $isDisabled();
    $isPrefixInline = $isPrefixInline();
    $isReorderable = (! $isDisabled) && $isReorderable();
    $isSuffixInline = $isSuffixInline();
    $placeholder = $getPlaceholder();
    $prefixActions = $getPrefixActions();
    $prefixIcon = $getPrefixIcon();
    $prefixIconColor = $getPrefixIconColor();
    $prefixLabel = $getPrefixLabel();
    $statePath = $getStatePath();
    $suffixActions = $getSuffixActions();
    $suffixIcon = $getSuffixIcon();
    $suffixIconColor = $getSuffixIconColor();
    $suffixLabel = $getSuffixLabel();
    $isSearchable = $isSearchable();
    $key = $getKey();
@endphp

<x-dynamic-component
    :component="$fieldWrapperView"
    :field="$field"
    class="fi-fo-tags-input-wrp"
>
    <x-filament::input.wrapper
        :disabled="$isDisabled"
        :inline-prefix="$isPrefixInline"
        :inline-suffix="$isSuffixInline"
        :prefix="$prefixLabel"
        :prefix-actions="$prefixActions"
        :prefix-icon="$prefixIcon"
        :prefix-icon-color="$prefixIconColor"
        :suffix="$suffixLabel"
        :suffix-actions="$suffixActions"
        :suffix-icon="$suffixIcon"
        :suffix-icon-color="$suffixIconColor"
        :valid="! $errors->has($statePath)"
        x-on:focus-input.stop="$el.querySelector('input')?.focus()"
        :attributes="
            \Filament\Support\prepare_inherited_attributes($attributes)
                ->merge($extraAttributes, escape: false)
                ->class([
                    'fi-fo-tags-input',
                    'fi-disabled' => $isDisabled,
                ])
        "
    >
        @if ($isSearchable)
            <div
                x-load
                x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('spatie-tags-input', 'filament/spatie-laravel-tags-plugin') }}"
                x-data="spatieTagsInputFormComponent({
                    state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
                    splitKeys: @js($getSplitKeys()),
                    searchDebounce: @js($getSearchDebounce()),
                    minSearchLength: @js($getMinSearchLength()),
                    noSearchResultsMessage: @js($getNoSearchResultsMessage()),
                    searchingMessage: @js($getSearchingMessage()),
                    searchPromptMessage: @js($getSearchPromptMessage()),
                    getSearchResultsUsing: async (search) => {
                        return await $wire.callSchemaComponentMethod(
                            @js($key),
                            'getSearchResultsForJs',
                            { search },
                        )
                    },
                })"
                class="relative w-full"
                {{ $getExtraAlpineAttributeBag() }}
            >
                <input
                    {{
                        $extraInputAttributeBag
                            ->merge([
                                'autocomplete' => 'off',
                                'autofocus' => $isAutofocused,
                                'disabled' => $isDisabled,
                                'id' => $id,
                                'placeholder' => filled($placeholder) ? e($placeholder) : null,
                                'type' => 'text',
                                'x-bind' => 'input',
                            ], escape: false)
                            ->class([
                                'fi-input',
                                'fi-input-has-inline-prefix' => $isPrefixInline && (count($prefixActions) || $prefixIcon || filled($prefixLabel)),
                                'fi-input-has-inline-suffix' => $isSuffixInline && (count($suffixActions) || $suffixIcon || filled($suffixLabel)),
                            ])
                    }}
                />

                {{-- Search results dropdown --}}
                <div
                    x-show="showDropdown"
                    x-cloak
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="opacity-0 -translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 -translate-y-1"
                    class="absolute left-0 right-0 z-20 mt-1 max-h-60 overflow-auto rounded-lg border border-gray-300 bg-white shadow-lg dark:border-gray-600 dark:bg-gray-800"
                >
                    {{-- Loading state --}}
                    <template x-if="isLoading">
                        <div class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400" x-text="searchingMessage"></div>
                    </template>

                    {{-- Prompt message (below min length) --}}
                    <template x-if="!isLoading && searchResults === null">
                        <div class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400" x-text="searchPromptMessage"></div>
                    </template>

                    {{-- No results --}}
                    <template x-if="!isLoading && searchResults !== null && searchResults.length === 0">
                        <div class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400" x-text="noSearchResultsMessage"></div>
                    </template>

                    {{-- Results list --}}
                    <template x-if="!isLoading && searchResults !== null && searchResults.length > 0">
                        <ul class="py-1">
                            <template x-for="(result, index) in searchResults" :key="result">
                                <li
                                    x-text="result"
                                    x-on:click="selectResult(result)"
                                    x-on:mouseenter="highlightedIndex = index"
                                    :class="{
                                        'bg-primary-500/10 text-primary-600 dark:text-primary-400': highlightedIndex === index,
                                        'text-gray-900 dark:text-gray-100': highlightedIndex !== index,
                                    }"
                                    class="cursor-pointer px-3 py-1.5 text-sm"
                                ></li>
                            </template>
                        </ul>
                    </template>
                </div>

                <div wire:ignore>
                    <template x-cloak x-if="state?.length">
                        <div
                            @if ($isReorderable)
                                x-on:end.stop="reorderTags($event)"
                                x-sortable
                                data-sortable-animation-duration="{{ $getReorderAnimationDuration() }}"
                            @endif
                            class="fi-fo-tags-input-tags-ctn"
                        >
                            <template
                                x-for="(tag, index) in state"
                                x-bind:key="`${tag}-${index}`"
                            >
                                <x-filament::badge
                                    :color="$color"
                                    :x-bind:x-sortable-item="$isReorderable ? 'index' : null"
                                    :x-sortable-handle="$isReorderable ? '' : null"
                                    @class([
                                        'fi-reorderable' => $isReorderable,
                                    ])
                                >
                                    {{ $getTagPrefix() }}

                                    <span x-text="tag"></span>

                                    {{ $getTagSuffix() }}

                                    <x-slot
                                        name="deleteButton"
                                        x-on:click.stop="deleteTag(tag)"
                                        :x-bind:aria-label="'\'' . __('filament-forms::components.tags_input.actions.delete.label') . ': \' + tag'"
                                    ></x-slot>
                                </x-filament::badge>
                            </template>
                        </div>
                    </template>
                </div>
            </div>
        @else
            {{-- Non-searchable mode: identical to original tags-input --}}
            <div
                x-load
                x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('tags-input', 'filament/forms') }}"
                x-data="tagsInputFormComponent({
                            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
                            splitKeys: @js($getSplitKeys()),
                        })"
                {{ $getExtraAlpineAttributeBag() }}
            >
                <input
                    {{
                        $extraInputAttributeBag
                            ->merge([
                                'autocomplete' => 'off',
                                'autofocus' => $isAutofocused,
                                'disabled' => $isDisabled,
                                'id' => $id,
                                'list' => $id . '-suggestions',
                                'placeholder' => filled($placeholder) ? e($placeholder) : null,
                                'type' => 'text',
                                'x-bind' => 'input',
                            ], escape: false)
                            ->class([
                                'fi-input',
                                'fi-input-has-inline-prefix' => $isPrefixInline && (count($prefixActions) || $prefixIcon || filled($prefixLabel)),
                                'fi-input-has-inline-suffix' => $isSuffixInline && (count($suffixActions) || $suffixIcon || filled($suffixLabel)),
                            ])
                    }}
                />

                <datalist id="{{ $id }}-suggestions">
                    @foreach ($getSuggestions() as $suggestion)
                        <template
                            x-bind:key="@js($suggestion)"
                            x-if="! (state?.includes(@js($suggestion)) ?? true)"
                        >
                            <option value="{{ $suggestion }}" />
                        </template>
                    @endforeach
                </datalist>

                <div wire:ignore>
                    <template x-cloak x-if="state?.length">
                        <div
                            @if ($isReorderable)
                                x-on:end.stop="reorderTags($event)"
                                x-sortable
                                data-sortable-animation-duration="{{ $getReorderAnimationDuration() }}"
                            @endif
                            class="fi-fo-tags-input-tags-ctn"
                        >
                            <template
                                x-for="(tag, index) in state"
                                x-bind:key="`${tag}-${index}`"
                            >
                                <x-filament::badge
                                    :color="$color"
                                    :x-bind:x-sortable-item="$isReorderable ? 'index' : null"
                                    :x-sortable-handle="$isReorderable ? '' : null"
                                    @class([
                                        'fi-reorderable' => $isReorderable,
                                    ])
                                >
                                    {{ $getTagPrefix() }}

                                    <span x-text="tag"></span>

                                    {{ $getTagSuffix() }}

                                    <x-slot
                                        name="deleteButton"
                                        x-on:click.stop="deleteTag(tag)"
                                        :x-bind:aria-label="'\'' . __('filament-forms::components.tags_input.actions.delete.label') . ': \' + tag'"
                                    ></x-slot>
                                </x-filament::badge>
                            </template>
                        </div>
                    </template>
                </div>
            </div>
        @endif
    </x-filament::input.wrapper>
</x-dynamic-component>
