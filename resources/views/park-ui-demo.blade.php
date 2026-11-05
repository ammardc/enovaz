@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-16">
    <!-- Header -->
    <div class="mb-16">
        <h1 class="text-4xl font-bold text-gray-900 mb-4">Park UI Components</h1>
        <p class="text-xl text-gray-600">Beautiful components inspired by Park UI, built with Tailwind CSS</p>
    </div>

    <!-- Buttons Section -->
    <section class="mb-20">
        <h2 class="text-2xl font-bold text-gray-900 mb-8">Buttons</h2>
        <x-card class="space-y-6">
            <div class="space-y-4">
                <h3 class="text-lg font-semibold text-gray-800">Variants</h3>
                <div class="flex flex-wrap gap-4">
                    <x-button variant="primary">Primary Button</x-button>
                    <x-button variant="secondary">Secondary Button</x-button>
                    <x-button variant="success">Success Button</x-button>
                    <x-button variant="error">Error Button</x-button>
                    <x-button variant="ghost">Ghost Button</x-button>
                </div>
            </div>

            <div class="space-y-4 border-t pt-6">
                <h3 class="text-lg font-semibold text-gray-800">Sizes</h3>
                <div class="flex flex-wrap gap-4 items-center">
                    <x-button size="sm">Small</x-button>
                    <x-button size="md">Medium</x-button>
                    <x-button size="lg">Large</x-button>
                    <x-button size="xl">Extra Large</x-button>
                </div>
            </div>

            <div class="space-y-4 border-t pt-6">
                <h3 class="text-lg font-semibold text-gray-800">States</h3>
                <div class="flex flex-wrap gap-4">
                    <x-button variant="primary">Active</x-button>
                    <x-button variant="primary" disabled>Disabled</x-button>
                </div>
            </div>
        </x-card>
    </section>

    <!-- Badges Section -->
    <section class="mb-20">
        <h2 class="text-2xl font-bold text-gray-900 mb-8">Badges</h2>
        <x-card class="space-y-6">
            <div class="flex flex-wrap gap-4">
                <x-badge variant="primary">Primary</x-badge>
                <x-badge variant="success">Success</x-badge>
                <x-badge variant="warning">Warning</x-badge>
                <x-badge variant="error">Error</x-badge>
                <x-badge variant="info">Info</x-badge>
            </div>

            <div class="border-t pt-6 space-y-4">
                <h3 class="text-lg font-semibold text-gray-800">With Icons/Text</h3>
                <div class="flex flex-wrap gap-4">
                    <x-badge variant="primary">✓ New</x-badge>
                    <x-badge variant="success">✓ Active</x-badge>
                    <x-badge variant="warning">⚠ Pending</x-badge>
                    <x-badge variant="error">✕ Failed</x-badge>
                </div>
            </div>
        </x-card>
    </section>

    <!-- Alerts Section -->
    <section class="mb-20">
        <h2 class="text-2xl font-bold text-gray-900 mb-8">Alerts</h2>
        <x-card class="space-y-6">
            <x-alert variant="info">
                <span>ℹ️</span>
                <span><strong>Info:</strong> This is an informational message. Use it to provide helpful context.</span>
            </x-alert>

            <x-alert variant="success">
                <span>✓</span>
                <span><strong>Success:</strong> Operation completed successfully.</span>
            </x-alert>

            <x-alert variant="warning">
                <span>⚠</span>
                <span><strong>Warning:</strong> Please review this before proceeding.</span>
            </x-alert>

            <x-alert variant="error">
                <span>✕</span>
                <span><strong>Error:</strong> Something went wrong. Please try again.</span>
            </x-alert>
        </x-card>
    </section>

    <!-- Form Inputs Section -->
    <section class="mb-20">
        <h2 class="text-2xl font-bold text-gray-900 mb-8">Form Inputs</h2>
        <x-card>
            <form class="space-y-6">
                <x-input 
                    label="Email Address" 
                    type="email" 
                    placeholder="hello@example.com"
                    required
                />

                <x-input 
                    label="Full Name" 
                    placeholder="John Doe"
                    required
                />

                <x-textarea 
                    label="Message" 
                    placeholder="Enter your message..."
                    rows="4"
                />

                <x-select label="Country" :options="['us' => 'United States', 'uk' => 'United Kingdom', 'ca' => 'Canada', 'au' => 'Australia']" />

                <div class="space-y-3 border-t pt-6">
                    <h3 class="text-lg font-semibold text-gray-800">Checkboxes</h3>
                    <x-checkbox label="I agree to the terms" />
                    <x-checkbox label="Subscribe to newsletter" />
                    <x-checkbox label="Remember me" checked="true" />
                </div>

                <div class="space-y-3 border-t pt-6">
                    <h3 class="text-lg font-semibold text-gray-800">Radio Buttons</h3>
                    <x-radio label="Option 1" name="option" />
                    <x-radio label="Option 2" name="option" />
                    <x-radio label="Option 3" name="option" />
                </div>

                <div class="border-t pt-6 flex gap-4">
                    <x-button variant="primary" type="submit">Submit</x-button>
                    <x-button variant="secondary" type="reset">Reset</x-button>
                </div>
            </form>
        </x-card>
    </section>

    <!-- Cards Section -->
    <section class="mb-20">
        <h2 class="text-2xl font-bold text-gray-900 mb-8">Cards</h2>
        <div class="grid md:grid-cols-2 gap-6">
            <x-card>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Basic Card</h3>
                <p class="text-gray-600">This is a simple card component with default styling. Perfect for organizing content.</p>
            </x-card>

            <x-card hoverable>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Hoverable Card</h3>
                <p class="text-gray-600">Hover over this card to see the shadow effect. Great for interactive content.</p>
            </x-card>

            <x-card>
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-gray-900">Feature Card</h3>
                    <p class="text-gray-600">Cards can contain any content, including other components.</p>
                    <div class="flex gap-2 pt-4">
                        <x-badge variant="primary">Component</x-badge>
                        <x-badge variant="success">Responsive</x-badge>
                    </div>
                </div>
            </x-card>

            <x-card>
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-gray-900">Call to Action</h3>
                    <p class="text-gray-600">Use cards to organize related actions and content together.</p>
                    <x-button variant="primary" class="w-full mt-4">Learn More</x-button>
                </div>
            </x-card>
        </div>
    </section>

    <!-- Combination Example -->
    <section>
        <h2 class="text-2xl font-bold text-gray-900 mb-8">Component Combination</h2>
        <x-card hoverable>
            <div class="space-y-6">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="text-2xl font-bold text-gray-900">Premium Plan</h3>
                        <p class="text-gray-600 mt-2">Everything you need to get started</p>
                    </div>
                    <x-badge variant="success">Popular</x-badge>
                </div>

                <x-alert variant="info">
                    <span>ℹ️</span>
                    <span>Free for the first 30 days. No credit card required.</span>
                </x-alert>

                <div class="space-y-3 text-gray-700">
                    <p>✓ Unlimited projects</p>
                    <p>✓ Priority support</p>
                    <p>✓ Advanced analytics</p>
                    <p>✓ Team collaboration</p>
                </div>

                <div class="bg-gray-50 p-6 rounded-lg border border-gray-200">
                    <p class="text-4xl font-bold text-gray-900">$29 <span class="text-lg text-gray-600">/month</span></p>
                </div>

                <div class="flex gap-4">
                    <x-button variant="primary" class="flex-1">Get Started</x-button>
                    <x-button variant="ghost" class="flex-1">Learn More</x-button>
                </div>
            </div>
        </x-card>
    </section>

</div>
@endsection
