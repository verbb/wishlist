(function($){

if (typeof Craft.Wishlist === 'undefined') {
    Craft.Wishlist = {};
}

var elementTypeClass = 'verbb\\wishlist\\elements\\ListElement';

Craft.Wishlist.ListIndex = Craft.BaseElementIndex.extend({

    listTypes: null,

    $newListBtnGroup: null,
    $newListBtn: null,

    canCreateLists: false,

    afterInit: function() {
        // Find which list types are being shown as sources
        this.listTypes = [];

        for (var i = 0; i < this.$sources.length; i++) {
            var $source = this.$sources.eq(i),
                key = $source.data('key'),
                match = key.match(/^listType:(\d+)$/);

            if (match) {
                this.listTypes.push({
                    id: parseInt(match[1]),
                    handle: $source.data('handle'),
                    name: $source.text(),
                    editable: $source.data('editable')
                });

                if (!this.canCreateLists && $source.data('editable')) {
                    this.canCreateLists = true;
                }
            }
        }

        this.on('selectSource', $.proxy(this, 'updateButton'));
        this.base();
    },

    getDefaultSourceKey: function() {
        // Did they request a specific list type in the URL?
        if (this.settings.context === 'index' && typeof defaultListTypeHandle !== 'undefined') {
            for (var i = 0; i < this.$sources.length; i++) {
                var $source = $(this.$sources[i]);
                if ($source.data('handle') === defaultListTypeHandle) {
                    return $source.data('key');
                }
            }
        }

        return this.base();
    },

    updateButton: function() {
        // Get the handle of the selected source
        var selectedSourceHandle = this.$source.data('handle');

        // Update the New List button
        // ---------------------------------------------------------------------

        // Remove the old button, if there is one
        if (this.$newListBtnGroup) {
            this.$newListBtnGroup.remove();
        }

        // Are they viewing a list type source?
        var selectedListType;
        if (selectedSourceHandle) {
            for (var i = 0; i < this.listTypes.length; i++) {
                if (this.listTypes[i].handle === selectedSourceHandle) {
                    selectedListType = this.listTypes[i];
                    break;
                }
            }
        }

        // Are they allowed to create new lists?
        if (this.canCreateLists) {
            this.$newListBtnGroup = $('<div class="btngroup submit"/>');
            var $menuBtn;

            // If they are, show a primary "New list" button, and a dropdown of the other list types (if any).
            // Otherwise only show a menu button
            if (selectedListType) {
                var href = this._getListTypeTriggerHref(selectedListType),
                    label = (this.settings.context === 'index' ? Craft.t('wishlist', 'New list') : Craft.t('wishlist', 'New {listType} list', {listType: selectedListType.name}));
                this.$newListBtn = $('<a class="btn submit add icon" '+href+'>'+label+'</a>').appendTo(this.$newListBtnGroup);

                if (this.settings.context !== 'index') {
                    this.addListener(this.$newListBtn, 'click', function(ev) {
                        this._openCreateListModal(ev.currentTarget.getAttribute('data-id'));
                    });
                }

                if (this.listTypes.length > 1) {
                    $menuBtn = $('<div class="btn submit menubtn"></div>').appendTo(this.$newListBtnGroup);
                }
            } else {
                this.$newListBtn = $menuBtn = $('<div class="btn submit add icon menubtn">'+Craft.t('wishlist', 'New list')+'</div>').appendTo(this.$newListBtnGroup);
            }

            if ($menuBtn) {
                var menuHtml = '<div class="menu"><ul>';

                for (var i = 0; i < this.listTypes.length; i++) {
                    var listType = this.listTypes[i];

                    if (this.settings.context === 'index' || listType !== selectedListType) {
                        var href = this._getListTypeTriggerHref(listType),
                            label = (this.settings.context === 'index' ? listType.name : Craft.t('wishlist', 'New {listType} list', {listType: listType.name}));
                        menuHtml += '<li><a '+href+'">'+label+'</a></li>';
                    }
                }

                menuHtml += '</ul></div>';

                $(menuHtml).appendTo(this.$newListBtnGroup);
                var menuBtn = new Garnish.MenuBtn($menuBtn);

                if (this.settings.context !== 'index') {
                    menuBtn.on('optionSelect', $.proxy(function(ev) {
                        this._openCreateListModal(ev.option.getAttribute('data-id'));
                    }, this));
                }
            }

            this.addButton(this.$newListBtnGroup);
        }

        // Update the URL if we're on the Lists index
        // ---------------------------------------------------------------------

        if (this.settings.context === 'index' && typeof history !== 'undefined') {
            var uri = 'wishlist/lists';

            if (selectedSourceHandle) {
                uri += '/'+selectedSourceHandle;
            }

            history.replaceState({}, '', Craft.getUrl(uri));
        }
    },

    _getListTypeTriggerHref: function(listType)
    {
        if (this.settings.context === 'index') {
            return 'href="'+Craft.getUrl('wishlist/lists/'+listType.handle+'/new')+'"';
        } else {
            return 'data-id="'+listType.id+'"';
        }
    },

    _openCreateListModal: function(listTypeId)
    {
        if (this.$newListBtn.hasClass('loading')) {
            return;
        }

        // Find the list type
        var listType;

        for (var i = 0; i < this.listTypes.length; i++) {
            if (this.listTypes[i].id === listTypeId) {
                listType = this.listTypes[i];
                break;
            }
        }

        if (!listType) {
            return;
        }

        this.$newListBtn.addClass('inactive');
        var newListBtnText = this.$newListBtn.text();
        this.$newListBtn.text(Craft.t('wishlist', 'New {listType} list', {listType: listType.name}));

        new Craft.ElementEditor({
            hudTrigger: this.$newListBtnGroup,
            elementType: elementTypeClass,
            locale: this.locale,
            attributes: {
                typeId: listTypeId
            },
            onBeginLoading: $.proxy(function() {
                this.$newListBtn.addClass('loading');
            }, this),
            onEndLoading: $.proxy(function() {
                this.$newListBtn.removeClass('loading');
            }, this),
            onHideHud: $.proxy(function() {
                this.$newListBtn.removeClass('inactive').text(newListBtnText);
            }, this),
            onSaveElement: $.proxy(function(response) {
                // Make sure the right list type is selected
                var listTypeSourceKey = 'listType:'+listTypeId;

                if (this.sourceKey !== listTypeSourceKey) {
                    this.selectSourceByKey(listTypeSourceKey);
                }

                this.selectElementAfterUpdate(response.id);
                this.updateElements();
            }, this)
        });
    }
});

// Register it!
try {
    Craft.registerElementIndexClass(elementTypeClass, Craft.Wishlist.ListIndex);
}
catch(e) {
    // Already registered
}

})(jQuery);
