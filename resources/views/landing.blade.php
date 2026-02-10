<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Mushroom King</title>

@vite(['resources/css/app.css', 'resources/js/app.js'])

<script>
window.slides = [
  "{{ asset('images/landing/slide-1.jpg') }}",
  "{{ asset('images/landing/slide-2.jpg') }}",
  "{{ asset('images/landing/slide-3.jpg') }}",
];
</script>

</head>
<body class="bg-[#124C12]">
  @include('partials.header')

<div class="min-h-screen flex items-center">

<div class="max-w-7xl mx-auto px-6 w-full grid lg:grid-cols-2 gap-12 items-center">

<!-- LEFT -->
<div>

<h1 class="text-5xl lg:text-[82px] text-[#CEE027] leading-[82px] gilroy font-black font-weight: 700">
  Track, Share & Explore<br>
  Your Mushroom Season
</h1>

<p class="text-white mt-4 max-w-lg">
Track all your foraging adventures, total harvest weight, best days, and locations.
</p>

<a href="{{ route('register.flow.show') }}"
class="inline-block mt-6 bg-[#E9C0E9] px-6 py-4 rounded-full font-medium text-black">
Create Account
</a>

</div>

<!-- RIGHT SLIDER -->

<div class="relative">

<div
x-data="slider()"
x-init="init()"
x-ref="wrapper"

@mouseenter="pause()"
@mouseleave="resume()"

@pointerdown.prevent="startDrag($event)"
@pointermove.prevent="onDrag($event)"
@pointerup="endDrag()"
@pointercancel="endDrag()"
@pointerleave="endDrag()"

class="relative w-full aspect-square lg:w-[540px] lg:h-[540px] overflow-hidden"
>

<div
class="absolute inset-0 transition-transform ease-out flex lg:flex-col"
:class="isMoving ? 'duration-[2000ms]' : 'duration-0'"
:style="transformStyle"
>

<template x-for="(src, i) in slides" :key="i">

<div
data-slide
class="flex-shrink-0 w-full h-full"
:style="isMobile ? `margin-right:${gap}px` : `margin-bottom:${gap}px`"
>

<img
:src="src"
class="w-full h-full object-cover rounded-2xl"
/>

</div>

</template>

</div>
</div>

</div>

</div>

</div>

</body>
</html>
