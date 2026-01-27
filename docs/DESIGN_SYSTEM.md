# TALMA Practice Pal - Design System Documentation

## Overview

This document outlines the visual design language and patterns applied across the TALMA Practice Pal educational web application. The design system emphasizes a **warm, calm, modern, and student-friendly** aesthetic suitable for young learners and teachers.

## Design Principles

### Core Visual Principles

1. **Soft Structure**
   - Prefer rounded corners (`rounded-xl` / `rounded-2xl`)
   - Replace harsh borders with subtle shadows
   - Use light background surfaces (e.g., `slate-50` / `blue-50`)

2. **Hierarchy & Spacing**
   - Strengthen headings and section separation
   - Use generous spacing between major blocks
   - Ensure clear visual rhythm across pages

3. **Interactive Affordances**
   - Make clickable elements feel clickable
   - Subtle hover states (shadow, background shift, translate-y)
   - Consistent cursor and focus styles

4. **Typography**
   - Clear heading hierarchy
   - Calm body text
   - Avoid dense or cramped layouts
   - Keep line length readable

5. **Color Usage**
   - Use existing brand colors as accents, not fills
   - Prefer neutral backgrounds
   - Keep contrast accessible

## Design Tokens

### Backgrounds

```html
<!-- Main page background -->
<div class="bg-gradient-to-br from-blue-50 via-white to-purple-50 min-h-screen">

<!-- Card/Container background -->
<div class="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/60 shadow-sm p-6">
```

### Colors

- **Primary Actions**: `bg-blue-600` / `hover:bg-blue-700`
- **Success**: `bg-green-600` / `bg-green-50` with `text-green-800`
- **Warning**: `bg-yellow-100` / `text-yellow-700`
- **Danger**: `bg-red-100` / `text-red-700`
- **Neutral**: `bg-gray-100` / `text-gray-700`

### Border Radius

- **Small elements**: `rounded-lg` (0.5rem)
- **Forms/Inputs**: `rounded-xl` (0.75rem)
- **Cards/Containers**: `rounded-2xl` (1rem)

### Shadows

- **Subtle**: `shadow-sm`
- **Hover**: `hover:shadow-md`
- **Elevated**: `shadow-lg` or `shadow-xl`

### Spacing

- **Form groups**: `space-y-6`
- **Card padding**: `p-6` or `p-8`
- **Section gaps**: `mb-6` or `mb-8`
- **Grid gaps**: `gap-4` or `gap-6`

## Component Patterns

### Buttons

#### Primary Button
```html
<button class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 active:scale-95 transition-all duration-200 shadow-sm hover:shadow-md">
    Button Text
</button>
```

#### Secondary Button
```html
<a href="#" class="px-4 py-2 bg-gray-100 text-gray-700 font-semibold rounded-xl hover:bg-gray-200 transition-all duration-200">
    Cancel
</a>
```

#### Small Button
```html
<button class="px-3 py-1.5 text-sm bg-blue-100 text-blue-700 font-medium rounded-lg hover:bg-blue-200 transition-all duration-200">
    Small Action
</button>
```

### Forms

#### Form Container
```html
<div class="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/60 shadow-sm p-8">
    <form class="space-y-6">
        <!-- form fields -->
    </form>
</div>
```

#### Input Fields
```html
<div>
    <label for="field" class="block text-sm font-semibold text-gray-700 mb-2">
        Label <span class="text-red-500">*</span>
    </label>
    <input type="text" 
           id="field" 
           name="field" 
           class="w-full px-4 py-3 border border-gray-300 rounded-xl bg-white text-gray-800 placeholder-gray-400 focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition-all duration-200">
</div>
```

#### Select Dropdowns
```html
<select class="w-full px-4 py-3 border border-gray-300 rounded-xl bg-white text-gray-800 focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition-all duration-200">
    <!-- options -->
</select>
```

#### Checkboxes
```html
<label class="flex items-center gap-3 cursor-pointer">
    <input type="checkbox" 
           class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-400">
    <span class="text-gray-700 font-medium">Checkbox Label</span>
</label>
```

### Cards

#### Standard Card
```html
<div class="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/60 shadow-sm p-6 mb-6">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Card Title</h2>
    <!-- card content -->
</div>
```

#### Interactive Card (Hover)
```html
<a href="#" class="block bg-white rounded-2xl border-2 border-gray-200 p-6 shadow-sm hover:shadow-xl hover:border-blue-300 hover:-translate-y-1 transition-all duration-300">
    <!-- card content -->
</a>
```

### Tables

#### Table Container
```html
<div class="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/60 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50/80">
                <tr>
                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">Header</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200/60">
                <tr class="hover:bg-gray-50/50 transition-colors duration-150">
                    <td class="px-6 py-4 text-gray-800">Content</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
```

### Alerts/Notifications

#### Success Alert
```html
<div class="bg-green-50/90 backdrop-blur-sm border border-green-200 text-green-800 p-4 rounded-xl shadow-sm mb-6">
    <div class="flex items-center gap-3">
        <i class="fas fa-check-circle text-green-600"></i>
        <p class="font-medium">Success message</p>
    </div>
</div>
```

#### Error Alert
```html
<div class="bg-red-50/90 backdrop-blur-sm border border-red-200 text-red-800 p-4 rounded-xl shadow-sm mb-6">
    <div class="flex items-center gap-3">
        <i class="fas fa-exclamation-circle text-red-600"></i>
        <p class="font-medium">Error message</p>
    </div>
</div>
```

### Badges/Tags

#### Status Badge
```html
<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">
    Active
</span>
```

#### Info Badge
```html
<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-blue-100 text-blue-700">
    Grade 7
</span>
```

### Navigation

#### Header Navigation
```html
<header class="bg-white/90 backdrop-blur-sm border-b border-gray-200/60 shadow-sm sticky top-0 z-50">
    <nav class="container mx-auto px-4 py-4 flex justify-between items-center">
        <!-- nav content -->
    </nav>
</header>
```

#### Navigation Links
```html
<a href="#" class="px-3 py-2 text-gray-700 hover:text-blue-600 font-medium rounded-lg hover:bg-blue-50 transition-all duration-200">
    Link Text
</a>
```

### Filters/Search

#### Filter Section
```html
<div class="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/60 shadow-sm p-6 mb-6">
    <form class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
        <!-- filter fields -->
        <div class="flex gap-2">
            <button type="submit" class="flex-1 px-4 py-2.5 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-all duration-200">
                Filter
            </button>
            <a href="#" class="flex-1 px-4 py-2.5 bg-gray-100 text-gray-700 font-semibold rounded-xl hover:bg-gray-200 transition-all duration-200 text-center">
                Clear
            </a>
        </div>
    </form>
</div>
```

## Page Layout Patterns

### Standard Page Container
```html
<div class="container mx-auto px-4 py-6 max-w-7xl">
    <!-- page content -->
</div>
```

### Page Header
```html
<div class="flex flex-col md:flex-row md:justify-between md:items-start gap-4 mb-8">
    <div>
        <h1 class="text-3xl font-bold text-gray-800 mb-3">Page Title</h1>
        <!-- subtitle/metadata -->
    </div>
    <div class="flex flex-wrap gap-3">
        <!-- action buttons -->
    </div>
</div>
```

### Empty States
```html
<div class="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/60 shadow-sm p-12 text-center">
    <div class="text-6xl mb-4">📚</div>
    <h3 class="text-2xl font-bold text-gray-800 mb-3">No items yet</h3>
    <p class="text-gray-600 mb-6">Description text</p>
    <a href="#" class="inline-block px-6 py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-all duration-200 shadow-sm hover:shadow-md">
        Action Button
    </a>
</div>
```

## Transitions & Animations

### Standard Transitions
- **Duration**: `transition-all duration-200` (fast) or `duration-300` (smooth)
- **Hover Scale**: `active:scale-95` (button press feedback)
- **Hover Lift**: `hover:-translate-y-1` (card hover effect)

### Common Transition Patterns
```html
<!-- Button hover -->
class="... hover:bg-blue-700 active:scale-95 transition-all duration-200"

<!-- Card hover -->
class="... hover:shadow-xl hover:border-blue-300 hover:-translate-y-1 transition-all duration-300"

<!-- Link hover -->
class="... hover:text-blue-600 transition-colors duration-200"
```

## Responsive Patterns

### Grid Layouts
```html
<!-- Responsive grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

<!-- Responsive flex -->
<div class="flex flex-col md:flex-row gap-4">
```

### Mobile Considerations
- Use `flex-wrap` for button groups
- Stack form fields vertically on mobile
- Ensure touch targets are at least 44x44px
- Use `md:` prefix for desktop-specific styles

## Accessibility

### Focus States
```html
<!-- Ensure all interactive elements have visible focus -->
class="... focus:ring-2 focus:ring-blue-400 focus:outline-none"
```

### Color Contrast
- Text on colored backgrounds should meet WCAG AA standards
- Use `text-gray-800` for primary text, `text-gray-600` for secondary
- Ensure sufficient contrast for all text/background combinations

## Implementation Notes

### Backdrop Blur
- Use `backdrop-blur-sm` for subtle depth
- Combine with opacity (`/90`, `/80`) for softer surfaces
- Provides modern, layered appearance

### Opacity Usage
- `bg-white/90` - Semi-transparent white (cards)
- `bg-gray-50/80` - Semi-transparent gray (subtle backgrounds)
- Creates depth without harsh borders

### Shadow Hierarchy
- `shadow-sm` - Default card shadow
- `hover:shadow-md` - Interactive feedback
- `shadow-lg` - Elevated/modal elements

## Files Updated

### Layouts
- `resources/views/layouts/app.blade.php`
- `resources/views/layouts/admin.blade.php`

### Admin Pages
- `resources/views/admin/login.blade.php`
- `resources/views/admin/lessons/index.blade.php`
- `resources/views/admin/lessons/create.blade.php`
- `resources/views/admin/lessons/manage.blade.php`
- `resources/views/admin/lessons/show.blade.php`

### Student Pages
- Already styled with Tailwind (from previous work)

## Next Steps

### Immediate Next Step

After Cursor finishes, do a second pass prompt:

**"Review the applied styles and remove any inconsistencies or over-styling. Simplify where possible."**

This second pass should:
- Remove duplicate or redundant styles
- Consolidate similar patterns
- Ensure consistent spacing and sizing
- Simplify overly complex class combinations
- Verify all interactive elements have proper hover/focus states
- Check for unused or conflicting styles

### Future Enhancements

1. **Complete remaining admin pages** - Apply patterns to:
   - Vocabulary management pages
   - Activity CRUD pages (matching games, flashcards, etc.)
   - User management
   - Dashboard/analytics pages

2. **Activity play pages** - Ensure consistency across:
   - Matching games play page
   - Flashcard games play page
   - Prompts play page
   - Other activity play pages

3. **Form normalization** - Standardize all forms:
   - Consistent field spacing
   - Unified validation error display
   - Standardized button placement

4. **Component extraction** - Consider creating reusable Blade components for:
   - Form fields
   - Cards
   - Buttons
   - Alerts
   - Tables

## Design Decisions

### Why Backdrop Blur?
- Creates subtle depth without heavy borders
- Modern, friendly aesthetic
- Maintains readability while adding visual interest

### Why Rounded Corners?
- Soft, approachable feel
- Less harsh than sharp corners
- Better for young learners

### Why Warm Gradients?
- Inviting and friendly
- Not corporate or sterile
- Appropriate for educational context

### Why Opacity?
- Creates layered, soft appearance
- Allows background colors to show through subtly
- More sophisticated than flat colors

## Maintenance

When adding new pages or components:
1. Reference this document for patterns
2. Use the established tokens and classes
3. Maintain consistency with existing pages
4. Test responsive behavior
5. Ensure accessibility (focus states, contrast)

---

**Last Updated**: January 27, 2026
**Status**: In Progress - Core patterns established, second pass needed
