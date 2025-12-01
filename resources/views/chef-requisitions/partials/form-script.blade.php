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
            // Sample items from Item Master (in production, fetch from API)
            availableItems: [
                { id: 1, name: 'Tomatoes', category: 'Vegetables', uom: 'kg', vendor: 'Fresh Farm Suppliers', price: 3500, stock: 45 },
                { id: 2, name: 'Onions', category: 'Vegetables', uom: 'kg', vendor: 'Fresh Farm Suppliers', price: 2800, stock: 60 },
                { id: 3, name: 'Carrots', category: 'Vegetables', uom: 'kg', vendor: 'Fresh Farm Suppliers', price: 3200, stock: 30 },
                { id: 4, name: 'Bell Peppers', category: 'Vegetables', uom: 'kg', vendor: 'Fresh Farm Suppliers', price: 5500, stock: 18 },
                { id: 5, name: 'Chicken Breast', category: 'Meat', uom: 'kg', vendor: 'Quality Meats Ltd', price: 12000, stock: 30 },
                { id: 6, name: 'Beef Sirloin', category: 'Meat', uom: 'kg', vendor: 'Quality Meats Ltd', price: 18000, stock: 25 },
                { id: 7, name: 'Pork Chops', category: 'Meat', uom: 'kg', vendor: 'Quality Meats Ltd', price: 14000, stock: 20 },
                { id: 8, name: 'Fresh Salmon', category: 'Seafood', uom: 'kg', vendor: 'Ocean Fresh Suppliers', price: 25000, stock: 10 },
                { id: 9, name: 'Prawns', category: 'Seafood', uom: 'kg', vendor: 'Ocean Fresh Suppliers', price: 28000, stock: 8 },
                { id: 10, name: 'Fresh Milk', category: 'Dairy', uom: 'L', vendor: 'Dairy Delights Co', price: 3000, stock: 50 },
                { id: 11, name: 'Butter', category: 'Dairy', uom: 'kg', vendor: 'Dairy Delights Co', price: 8000, stock: 12 },
                { id: 12, name: 'Cheddar Cheese', category: 'Dairy', uom: 'kg', vendor: 'Dairy Delights Co', price: 15000, stock: 8 },
                { id: 13, name: 'Rice (Basmati)', category: 'Grains', uom: 'kg', vendor: 'Grain Wholesalers', price: 4200, stock: 100 },
                { id: 14, name: 'Pasta (Spaghetti)', category: 'Grains', uom: 'kg', vendor: 'Grain Wholesalers', price: 3500, stock: 80 },
                { id: 15, name: 'Olive Oil', category: 'Cooking Oils', uom: 'L', vendor: 'Premium Foods Co', price: 8500, stock: 12 },
                { id: 16, name: 'Vegetable Oil', category: 'Cooking Oils', uom: 'L', vendor: 'Premium Foods Co', price: 5000, stock: 20 },
                { id: 17, name: 'Black Pepper', category: 'Spices', uom: 'g', vendor: 'Spice Market Ltd', price: 15000, stock: 500 },
                { id: 18, name: 'Salt', category: 'Spices', uom: 'kg', vendor: 'Spice Market Ltd', price: 1500, stock: 50 },
                { id: 19, name: 'Bananas', category: 'Fruits', uom: 'kg', vendor: 'Fresh Farm Suppliers', price: 2500, stock: 40 },
                { id: 20, name: 'Apples', category: 'Fruits', uom: 'kg', vendor: 'Fresh Farm Suppliers', price: 4500, stock: 25 }
            ],
            rawInitialItems: [],
            items: [],
            currencyMeta: window.appCurrency || { code: 'USD', symbol: '$', precision: 2 },
            init() {
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
                    originalPrice: 0
                };
            },
            hydrateItem(item) {
                const base = this.emptyItem();
                const merged = { ...base, ...item };

                merged.item_id = item.item_id ?? item.itemId ?? '';
                merged.vendor = item.vendor ?? base.vendor;
                merged.uom = item.uom ?? item.unit ?? base.uom;

                const price = parseFloat(item.price ?? base.price) || 0;
                const quantity = parseFloat(item.quantity ?? base.quantity) || 0;
                const defaultPrice = parseFloat(item.defaultPrice ?? item.originalPrice ?? price) || 0;
                const priceEdited = typeof item.priceEdited !== 'undefined'
                    ? this.parseBoolean(item.priceEdited)
                    : (Math.round(price * 100) !== Math.round(defaultPrice * 100));

                merged.price = price;
                merged.quantity = quantity;
                merged.defaultPrice = defaultPrice;
                merged.originalPrice = parseFloat(item.originalPrice ?? defaultPrice ?? price) || price;
                merged.lineTotal = price * quantity;
                merged.priceEdited = priceEdited;

                return merged;
            },
            parseBoolean(value) {
                if (typeof value === 'string') {
                    return ['1', 'true', 'yes', 'on'].includes(value.toLowerCase());
                }
                return Boolean(value);
            },
            
            get groupedItems() {
                const grouped = {};
                this.availableItems.forEach(item => {
                    if (!grouped[item.category]) {
                        grouped[item.category] = [];
                    }
                    grouped[item.category].push(item);
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
                const selectedId = this.items[index].item_id;
                if (!selectedId) return;
                
                const item = this.availableItems.find(i => i.id == selectedId);
                if (item) {
                    this.items[index].vendor = item.vendor;
                    this.items[index].price = item.price;
                    this.items[index].defaultPrice = item.price;
                    this.items[index].originalPrice = item.price;
                    this.items[index].uom = item.uom;
                    this.items[index].priceEdited = false;
                    this.updateLineTotal(index);
                }
            },
            
            updateLineTotal(index) {
                const row = this.items[index];
                const price = parseFloat(row.price) || 0;
                const quantity = parseFloat(row.quantity) || 0;
                row.lineTotal = price * quantity;
            },
            
            trackPriceChange(index) {
                const row = this.items[index];
                if (row.defaultPrice && parseFloat(row.price) !== parseFloat(row.defaultPrice)) {
                    row.priceEdited = true;
                } else {
                    row.priceEdited = false;
                }
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
                const item = this.availableItems.find(i => i.id == itemId);
                return item ? item.name : '';
            },
            
            isFormValid() {
                return this.items.every(item => 
                    item.item_id && 
                    item.price > 0 && 
                    item.quantity > 0
                );
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
                event.target.submit();
            }
        };

        return state;
    }
</script>
