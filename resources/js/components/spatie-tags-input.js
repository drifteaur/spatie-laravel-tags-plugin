export default function spatieTagsInputFormComponent({
    state,
    splitKeys,
    searchDebounce,
    minSearchLength,
    noSearchResultsMessage,
    searchingMessage,
    searchPromptMessage,
    getSearchResultsUsing,
}) {
    return {
        newTag: '',
        state,

        // Search state
        searchResults: null,
        showDropdown: false,
        isLoading: false,
        highlightedIndex: -1,
        activeSearchId: 0,
        searchTimeout: null,

        // Config
        searchDebounce,
        minSearchLength,
        noSearchResultsMessage,
        searchingMessage,
        searchPromptMessage,

        createTag() {
            this.newTag = this.newTag.trim()

            if (this.newTag === '') {
                return
            }

            if (this.state.includes(this.newTag)) {
                this.newTag = ''
                this.closeDropdown()
                return
            }

            this.state.push(this.newTag)
            this.newTag = ''
            this.closeDropdown()
        },

        deleteTag(tagToDelete) {
            this.state = this.state.filter((tag) => tag !== tagToDelete)
        },

        reorderTags(event) {
            const reordered = this.state.splice(event.oldIndex, 1)[0]
            this.state.splice(event.newIndex, 0, reordered)
            this.state = [...this.state]
        },

        selectResult(result) {
            if (this.state.includes(result)) {
                return
            }

            this.state.push(result)
            this.newTag = ''
            this.closeDropdown()

            this.$nextTick(() => {
                this.$el.querySelector('input')?.focus()
            })
        },

        closeDropdown() {
            this.showDropdown = false
            this.searchResults = null
            this.highlightedIndex = -1
            this.isLoading = false

            if (this.searchTimeout) {
                clearTimeout(this.searchTimeout)
                this.searchTimeout = null
            }

            this.activeSearchId++
        },

        async handleSearch() {
            const query = this.newTag.trim()

            if (this.searchTimeout) {
                clearTimeout(this.searchTimeout)
                this.searchTimeout = null
            }

            if (query.length < this.minSearchLength) {
                this.searchResults = null
                this.showDropdown = query.length > 0
                this.isLoading = false
                this.highlightedIndex = -1
                return
            }

            this.showDropdown = true
            this.isLoading = true
            this.highlightedIndex = -1

            this.searchTimeout = setTimeout(async () => {
                this.searchTimeout = null

                const searchId = ++this.activeSearchId

                try {
                    const results = await getSearchResultsUsing(query)

                    if (searchId !== this.activeSearchId) {
                        return
                    }

                    const filtered = (results || []).filter(
                        (r) => !this.state.includes(r),
                    )

                    this.searchResults = filtered
                    this.isLoading = false
                    this.highlightedIndex = filtered.length > 0 ? 0 : -1
                } catch (error) {
                    if (searchId === this.activeSearchId) {
                        console.error(
                            'Error fetching tag search results:',
                            error,
                        )
                        this.searchResults = []
                        this.isLoading = false
                    }
                }
            }, this.searchDebounce)
        },

        input: {
            ['x-on:blur']() {
                setTimeout(() => {
                    if (
                        !this.$el
                            .closest('[x-data]')
                            ?.contains(document.activeElement)
                    ) {
                        this.createTag()
                        this.closeDropdown()
                    }
                }, 200)
            },
            ['x-model']: 'newTag',
            ['x-on:input']() {
                this.handleSearch()
            },
            ['x-on:keydown'](event) {
                if (event.key === 'ArrowDown') {
                    event.preventDefault()
                    if (
                        this.searchResults?.length &&
                        this.highlightedIndex < this.searchResults.length - 1
                    ) {
                        this.highlightedIndex++
                    }
                    return
                }

                if (event.key === 'ArrowUp') {
                    event.preventDefault()
                    if (this.highlightedIndex > 0) {
                        this.highlightedIndex--
                    }
                    return
                }

                if (event.key === 'Escape') {
                    event.preventDefault()
                    this.closeDropdown()
                    return
                }

                if (
                    event.key === 'Enter' ||
                    splitKeys.includes(event.key)
                ) {
                    event.preventDefault()
                    event.stopPropagation()

                    if (
                        this.highlightedIndex >= 0 &&
                        this.searchResults?.[this.highlightedIndex]
                    ) {
                        this.selectResult(
                            this.searchResults[this.highlightedIndex],
                        )
                    } else {
                        this.createTag()
                    }
                    return
                }
            },
            ['x-on:focus']() {
                const query = this.newTag.trim()
                if (query.length >= this.minSearchLength) {
                    this.handleSearch()
                }
            },
            ['x-on:paste']() {
                this.$nextTick(() => {
                    if (splitKeys.length === 0) {
                        this.createTag()
                        return
                    }

                    const pattern = splitKeys
                        .map((key) =>
                            key.replace(/[/\-\\^$*+?.()|[\]{}]/g, '\\$&'),
                        )
                        .join('|')

                    this.newTag
                        .split(new RegExp(pattern, 'g'))
                        .forEach((tag) => {
                            this.newTag = tag
                            this.createTag()
                        })
                })
            },
        },
    }
}
