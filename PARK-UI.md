# Park UI Components - Documentation

**Park UI components** terinspirasi dari [park-ui.com](https://park-ui.com/), diintegrasikan dengan Laravel 12 & Tailwind CSS v4.

## 🎯 Demo Page

Lihat semua components di: **http://localhost:8000/demo/park-ui**

---

## 📦 Komponen Tersedia

### 1. Button Component
Tombol dengan berbagai variant dan ukuran.

**File:** `resources/views/components/button.blade.php`

**Variants:** `primary`, `secondary`, `success`, `error`, `ghost`
**Sizes:** `sm`, `md`, `lg`, `xl`
**States:** `disabled`, `active`

**Penggunaan:**
```blade
<!-- Primary Button -->
<x-button variant="primary">Click Me</x-button>

<!-- Secondary Button, Small Size -->
<x-button variant="secondary" size="sm">Small Button</x-button>

<!-- Success Button, Disabled -->
<x-button variant="success" disabled>Disabled</x-button>

<!-- Ghost Button as Link -->
<x-button variant="ghost" type="button">Learn More</x-button>

<!-- Dengan Props Custom -->
<x-button class="w-full">Full Width Button</x-button>
```

---

### 2. Badge Component
Label kecil untuk status, tags, atau kategorisasi.

**File:** `resources/views/components/badge.blade.php`

**Variants:** `primary`, `success`, `warning`, `error`, `info`

**Penggunaan:**
```blade
<x-badge variant="primary">New</x-badge>
<x-badge variant="success">✓ Active</x-badge>
<x-badge variant="warning">⚠ Pending</x-badge>
<x-badge variant="error">✕ Failed</x-badge>
```

---

### 3. Card Component
Container untuk mengorganisir konten.

**File:** `resources/views/components/card.blade.php`

**Props:**
- `hoverable` (boolean) - Tambah hover effect shadow

**Penggunaan:**
```blade
<x-card>
    <h3>Card Title</h3>
    <p>Card content goes here</p>
</x-card>

<!-- Hoverable Card -->
<x-card hoverable>
    <h3>Interactive Card</h3>
    <p>Hover untuk melihat shadow effect</p>
</x-card>
```

---

### 4. Alert Component
Menampilkan pesan info, success, warning, atau error.

**File:** `resources/views/components/alert.blade.php`

**Variants:** `info`, `success`, `warning`, `error`

**Penggunaan:**
```blade
<x-alert variant="info">
    <span>ℹ️</span>
    <span>Informational message</span>
</x-alert>

<x-alert variant="success">
    <span>✓</span>
    <span>Operation successful!</span>
</x-alert>

<x-alert variant="error">
    <span>✕</span>
    <span>Something went wrong</span>
</x-alert>
```

---

### 5. Input Component
Text input dengan label dan error handling.

**File:** `resources/views/components/input.blade.php`

**Props:**
- `label` (string) - Label untuk input
- `type` (string) - Input type (default: `text`)
- `error` (string) - Error message
- Plus HTML input attributes

**Penggunaan:**
```blade
<x-input 
    label="Email Address"
    type="email"
    placeholder="hello@example.com"
    required
/>

<!-- Dengan Error -->
<x-input 
    label="Username"
    error="Username sudah digunakan"
    value="john_doe"
/>

<!-- Password Input -->
<x-input 
    label="Password"
    type="password"
    required
/>
```

---

### 6. Textarea Component
Multi-line text input.

**File:** `resources/views/components/textarea.blade.php`

**Props:**
- `label` (string) - Label
- `error` (string) - Error message
- Plus HTML textarea attributes

**Penggunaan:**
```blade
<x-textarea 
    label="Message"
    placeholder="Enter your message..."
    rows="4"
    required
/>

<!-- Dengan Error -->
<x-textarea 
    label="Description"
    error="Description harus minimal 10 karakter"
    value="Previous content"
/>
```

---

### 7. Select Component
Dropdown select input.

**File:** `resources/views/components/select.blade.php`

**Props:**
- `label` (string) - Label
- `options` (array) - Array of `['value' => 'label']`
- `error` (string) - Error message

**Penggunaan:**
```blade
<x-select 
    label="Country"
    :options="[
        'us' => 'United States',
        'uk' => 'United Kingdom',
        'ca' => 'Canada',
    ]"
    name="country"
/>

<!-- Dengan Error -->
<x-select 
    label="Plan"
    :options="$plans"
    error="Please select a plan"
    name="plan_id"
/>
```

---

### 8. Checkbox Component
Checkbox input dengan label.

**File:** `resources/views/components/checkbox.blade.php`

**Props:**
- `label` (string) - Label text
- `checked` (boolean) - Pre-check checkbox
- `error` (string) - Error message

**Penggunaan:**
```blade
<x-checkbox 
    label="I agree to the terms"
    name="terms"
    required
/>

<!-- Pre-checked -->
<x-checkbox 
    label="Subscribe to newsletter"
    name="newsletter"
    checked="true"
/>
```

---

### 9. Radio Component
Radio button dengan label.

**File:** `resources/views/components/radio.blade.php`

**Props:**
- `label` (string) - Label text
- `error` (string) - Error message

**Penggunaan:**
```blade
<div class="space-y-3">
    <x-radio label="Option 1" name="choice" value="1" />
    <x-radio label="Option 2" name="choice" value="2" />
    <x-radio label="Option 3" name="choice" value="3" />
</div>
```

---

## 🎨 Color Palette

Park UI menggunakan palette yang clean dan modern:

| Color | Usage | Hex |
|-------|-------|-----|
| **Primary** | Buttons, links, highlights | `#6b2cdd` |
| **Primary Dark** | Hover state | `#5520c0` |
| **Success** | Positive actions, badges | `#10b981` |
| **Warning** | Caution, pending | `#f59e0b` |
| **Error** | Destructive, errors | `#ef4444` |
| **Info** | Information alerts | `#3b82f6` |
| **Gray-900** | Text, headings | `#111827` |
| **Gray-600** | Muted text | `#4b5563` |

Gunakan colors ini di Tailwind dengan syntax `[#hex]`:
```blade
<x-button class="bg-[#6b2cdd] hover:bg-[#5520c0]">
    Custom Color Button
</x-button>
```

---

## 💡 Usage Examples

### Form dengan Park UI Components
```blade
<x-card>
    <form action="/submit" method="POST" class="space-y-6">
        @csrf
        
        <x-input 
            label="Full Name"
            name="name"
            placeholder="John Doe"
            error="{{ $errors->has('name') ? $errors->first('name') : null }}"
            value="{{ old('name') }}"
            required
        />
        
        <x-input 
            label="Email"
            type="email"
            name="email"
            error="{{ $errors->has('email') ? $errors->first('email') : null }}"
            value="{{ old('email') }}"
            required
        />
        
        <x-textarea 
            label="Message"
            name="message"
            error="{{ $errors->has('message') ? $errors->first('message') : null }}"
            rows="5"
            required
        />
        
        <x-checkbox 
            label="I agree to the terms"
            name="terms"
            error="{{ $errors->has('terms') ? $errors->first('terms') : null }}"
            required
        />
        
        <div class="flex gap-4">
            <x-button variant="primary" type="submit" class="flex-1">
                Submit
            </x-button>
            <x-button variant="ghost" type="reset" class="flex-1">
                Reset
            </x-button>
        </div>
    </form>
</x-card>
```

### Product Card
```blade
<x-card hoverable class="flex flex-col">
    <div class="mb-4">
        <img src="{{ $product->image }}" alt="{{ $product->name }}" class="w-full h-48 object-cover rounded-lg">
    </div>
    
    <h3 class="text-lg font-semibold text-gray-900 mb-2">
        {{ $product->name }}
    </h3>
    
    <p class="text-gray-600 text-sm mb-4">{{ $product->description }}</p>
    
    <div class="flex items-center gap-2 mb-4">
        <x-badge variant="primary">{{ $product->category }}</x-badge>
        @if($product->is_new)
            <x-badge variant="success">New</x-badge>
        @endif
    </div>
    
    <div class="text-2xl font-bold text-gray-900 mb-4">
        {{ $product->formatted_price }}
    </div>
    
    <x-button variant="primary" class="w-full mt-auto">
        Add to Cart
    </x-button>
</x-card>
```

### Status Display
```blade
<x-card>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <span class="text-gray-700">Order Status</span>
            @switch($order->status)
                @case('pending')
                    <x-badge variant="warning">Pending</x-badge>
                    @break
                @case('completed')
                    <x-badge variant="success">Completed</x-badge>
                    @break
                @case('failed')
                    <x-badge variant="error">Failed</x-badge>
                    @break
            @endswitch
        </div>
        
        @if($order->status === 'failed')
            <x-alert variant="error">
                <span>✕</span>
                <span>Order failed. Please try again.</span>
            </x-alert>
        @endif
    </div>
</x-card>
```

---

## 🔧 Customization

### Ubah Default Warna
Edit file `resources/css/app.css` untuk mengubah color palette:

```css
:root {
    --color-primary: #6b2cdd;      /* Ubah primary color */
    --color-primary-dark: #5520c0;
    --color-success: #10b981;
    /* ... */
}
```

### Tambah Custom Variant
Buat file baru di `resources/views/components/button.blade.php` dan tambahkan variant baru:

```php
$variantClasses = match($variant) {
    'primary' => '...',
    'secondary' => '...',
    'custom' => 'bg-[#123456] text-white hover:bg-[#0a1f3a]',  // Baru
    // ...
};
```

---

## 📱 Responsive Design

Semua components sudah responsive. Gunakan Tailwind responsive prefixes:

```blade
<x-button class="w-full md:w-auto px-4 md:px-8">
    Responsive Button
</x-button>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <x-card>...</x-card>
    <x-card>...</x-card>
    <x-card>...</x-card>
</div>
```

---

## ♿ Accessibility

Semua components mengikuti accessibility best practices:
- ✅ Semantic HTML (`<button>`, `<input>`, `<label>`)
- ✅ Focus visible states
- ✅ Proper ARIA attributes
- ✅ Label associations
- ✅ Color contrast ratios

---

## 🧪 Testing Components

Akses demo page untuk test:
```
http://localhost:8000/demo/park-ui
```

Atau test di console:
```blade
@php
    dump(view('components.button', ['variant' => 'primary'])->render());
@endphp
```

---

## 📦 CSS Size

Setelah build dengan Tailwind CSS:
- **Uncompressed:** 63.64 kB
- **Gzip:** 14.97 kB
- **Brotli:** ~12 kB

Ukuran sudah optimal dengan tree-shaking unused CSS.

---

## 🚀 Performance Tips

1. **Lazy load heavy components:**
   ```blade
   @if($show_detailed_card)
       <x-card>Heavy content</x-card>
   @endif
   ```

2. **Cache card HTML:**
   ```blade
   @cache($product, 'product_card_' . $product->id)
       <x-card>{{ $product->name }}</x-card>
   @endcache
   ```

3. **Use Alpine.js untuk interactivity:**
   ```blade
   <x-button @click="toggleModal = true">Open Modal</x-button>
   ```

---

## 🐛 Troubleshooting

### Components tidak render
- Check Tailwind CSS build: `npm run build`
- Verify component file exists: `resources/views/components/*.blade.php`
- Clear cache: `php artisan view:clear`

### Styling tidak apply
- Rebuild CSS: `npm run dev` atau `npm run build`
- Check if class names match Tailwind
- Use `!important` as last resort: `class="!bg-red-500"`

### Colors tidak sesuai
- Edit `resources/css/app.css` color variables
- Rebuild: `npm run build`
- Hard refresh browser: `Ctrl+Shift+R`

---

## 📚 Resources

- **Park UI:** https://park-ui.com/
- **Tailwind CSS:** https://tailwindcss.com/
- **Tailwind Components:** https://tailwindui.com/
- **Blade Components:** https://laravel.com/docs/12.x/blade#components

---

**Last Updated:** 2026-11-05
**Version:** 1.0.0

Enjoy building beautiful interfaces! 🎨
