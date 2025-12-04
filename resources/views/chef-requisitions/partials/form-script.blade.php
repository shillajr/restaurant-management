<script>
    function requisitionForm() {
        const normalizeInitial = (items) => {
            if (Array.isArray(items)) {
                return items;
            }

            if (items && typeof items === 'object') {
                return Object.values(items);
            }

            return [];
        };

        const state = {
            availableItems: [],
            rawInitialItems: [],
            items: [],
            currencyMeta: window.appCurrency || { code: 'USD', symbol: '$', precision: 2 },
            init() {
                this.availableItems = this.resolveAvailableItems();
                this.ensureInitialSelections();
                const source = this.rawInitialItems.length ? this.rawInitialItems : [this.emptyItem()];
                this.items = source.map(item => this.hydrateItem(item));
            },
            loadInitial(data) {
                this.rawInitialItems = normalizeInitial(data);
                this.init();
            },
            emptyItem() {
                return {
                    item_id: '',
                    vendor: '',
                    price: 0,
                    defaultPrice: 0,
                    quantity: 0,
                    uom: '',
                    lineTotal: 0,
                    priceEdited: false,
                    originalPrice: 0,
                    itemName: ''
                };
            },
            hydrateItem(item) {
                const base = this.emptyItem();
                const merged = { ...base, ...item };

                merged.item_id = this.normalizeId(item.item_id ?? item.itemId) ?? '';
                merged.vendor = item.vendor ?? base.vendor;
                merged.uom = item.uom ?? item.unit ?? base.uom;
                merged.itemName = item.itemName ?? item.item ?? item.item_name ?? base.itemName;

                const price = this.toNumber(item.price ?? base.price);
                const quantity = this.toNumber(item.quantity ?? base.quantity);
                const defaultPrice = this.toNumber(item.defaultPrice ?? item.originalPrice ?? price);
                const originalPrice = this.toNumber(item.originalPrice ?? defaultPrice ?? price);
                const priceEdited = typeof item.priceEdited !== 'undefined'
                    ? this.parseBoolean(item.priceEdited)
                    : this.pricesDiffer(price, defaultPrice);

                merged.price = price;
                merged.quantity = quantity;
                merged.defaultPrice = defaultPrice;
                merged.originalPrice = originalPrice;
                merged.lineTotal = price * quantity;
                merged.priceEdited = priceEdited;

                return merged;
            },
            resolveAvailableItems() {
                const config = window.requisitionFormConfig || {};
                const items = Array.isArray(config.availableItems) ? config.availableItems : [];

                const normalized = items.map(raw => ({
                    id: this.normalizeId(raw.id),
                    name: raw.name ?? 'Unnamed Item',
                    category: raw.category ?? 'Other Items',
                    uom: raw.uom ?? '',
                    vendor: raw.vendor ?? '',
                    price: this.toNumber(raw.price),
                    legacy: Boolean(raw.legacy || false),
                })).filter(item => item.id !== null);

                normalized.sort((a, b) => this.sortByCategoryThenName(a, b));
                return normalized;
            },
            ensureInitialSelections() {
                this.rawInitialItems.forEach(item => {
                    const itemId = this.normalizeId(item.item_id ?? item.itemId);
                    if (!itemId) {
                        return;
                    }

                    const exists = this.availableItems.some(available => available.id === itemId);
                    if (exists) {
                        return;
                    }

                    const fallbackName = item.itemName ?? item.item ?? item.item_name ?? `Item #${itemId}`;

                    this.availableItems.push({
                        id: itemId,
                        name: fallbackName,
                        category: 'Legacy Items',
                        uom: item.uom ?? item.unit ?? '',
                        vendor: item.vendor ?? '',
                        price: this.toNumber(item.defaultPrice ?? item.originalPrice ?? item.price),
                        legacy: true,
                    });
                });

                this.availableItems.sort((a, b) => this.sortByCategoryThenName(a, b));
            },
            sortByCategoryThenName(a, b) {
                const categoryCompare = (a.category || 'Other Items').localeCompare(b.category || 'Other Items');
                if (categoryCompare !== 0) {
                    return categoryCompare;
                }
                return (a.name || '').localeCompare(b.name || '');
            },
            normalizeId(value) {
                const parsed = parseInt(value, 10);
                return Number.isFinite(parsed) ? parsed : null;
            },
            toNumber(value) {
                const parsed = parseFloat(value);
                return Number.isFinite(parsed) ? parsed : 0;
            },
            parseBoolean(value) {
                if (typeof value === 'string') {
                    return ['1', 'true', 'yes', 'on'].includes(value.toLowerCase());
                }
                return Boolean(value);
            },
            pricesDiffer(a, b) {
                return Math.abs(this.toNumber(a) - this.toNumber(b)) > 0.0001;
            },
            
            get groupedItems() {
                const grouped = {};
                this.availableItems.forEach(item => {
                    const category = item.category || 'Other Items';
                    if (!grouped[category]) {
                        grouped[category] = [];
                    }
                    grouped[category].push(item);
                });

                Object.keys(grouped).forEach(category => {
                    grouped[category].sort((a, b) => (a.name || '').localeCompare(b.name || ''));
                });
                return grouped;
            },
            
            get subtotal() {
                return this.items.reduce((sum, item) => sum + (item.lineTotal || 0), 0);
            },
            
            get grandTotal() {
                return this.subtotal; // Add taxes/charges here if needed
            },
            
            get totalQuantity() {
                return this.items.reduce((sum, item) => sum + (parseFloat(item.quantity) || 0), 0);
            },
            
            get modifiedPricesCount() {
                return this.items.filter(item => item.priceEdited).length;
            },
            
            selectItem(index) {
                const selectedId = this.normalizeId(this.items[index].item_id);
                if (!selectedId) return;
                
                const item = this.availableItems.find(i => i.id === selectedId);
                if (item) {
                    this.items[index].item_id = selectedId;
                    this.items[index].vendor = item.vendor;
                    this.items[index].price = this.toNumber(item.price);
                    this.items[index].defaultPrice = this.toNumber(item.price);
                    this.items[index].originalPrice = this.toNumber(item.price);
                    this.items[index].uom = item.uom;
                    this.items[index].itemName = item.name;
                    this.items[index].priceEdited = false;
                    this.updateLineTotal(index);
                }
            },
            
            updateLineTotal(index) {
                const row = this.items[index];
                const price = this.toNumber(row.price);
                const quantity = this.toNumber(row.quantity);
                row.lineTotal = price * quantity;
            },
            
            trackPriceChange(index) {
                const row = this.items[index];
                row.priceEdited = row.defaultPrice
                    ? this.pricesDiffer(row.price, row.defaultPrice)
                    : false;
            },
            
            addItem() {
                this.items.push(this.emptyItem());
            },
            
            removeItem(index) {
                if (this.items.length > 1) {
                    this.items.splice(index, 1);
                }
            },
            
            formatCurrency(amount) {
                const currency = this.currencyMeta;
                const precision = Number.isInteger(currency.precision) ? currency.precision : 2;
                const numericAmount = parseFloat(amount ?? 0) || 0;

                return `${currency.code} ${numericAmount.toLocaleString('en-US', {
                    minimumFractionDigits: precision,
                    maximumFractionDigits: precision,
                })}`;
            },
            
            getItemName(itemId) {
                const normalizedId = this.normalizeId(itemId);
                if (!normalizedId) {
                    return '';
                }

                const item = this.availableItems.find(i => i.id === normalizedId);
                if (item) {
                    return item.name;
                }

                const fallbackRow = this.items.find(row => this.normalizeId(row.item_id) === normalizedId);
                return fallbackRow ? (fallbackRow.itemName || '') : '';
            },
            
            isFormValid() {
                return this.items.every(item => {
                    const hasItem = Boolean(this.normalizeId(item.item_id));
                    return hasItem && this.toNumber(item.price) > 0 && this.toNumber(item.quantity) > 0;
                });
            },
            
            submitForm(event) {
                if (!this.isFormValid()) {
                    alert('Please fill in all required fields for each item.');
                    return;
                }
                
                // If there are modified prices, confirm with user
                if (this.modifiedPricesCount > 0) {
                    if (!confirm(`You have modified ${this.modifiedPricesCount} price(s). Do you want to proceed?`)) {
                        return;
                    }
                }
                
                // Submit the form
                const form = event?.target?.closest('form');
                if (form) {
                    form.submit();
                }
            }
        };

        return state;
    }
</script>
