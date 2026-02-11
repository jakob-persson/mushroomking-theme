<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Mushroom King</title>

@vite(['resources/css/app.css', 'resources/js/app.js'])

<script>
window.slides = [
  "{{ asset('images/landing/1-frame.png') }}",
  "{{ asset('images/landing/2-frame.png') }}",
  "{{ asset('images/landing/3-frame.png') }}",
  "{{ asset('images/landing/4-frame.png') }}",
  "{{ asset('images/landing/5-frame.png') }}",
];
</script>

</head>
<body class="bg-[#124C12]">
  @include('partials.header')

<div class="lg:min-h-[88vh] flex items-center overflow-hidden">

<div class="max-w-7xl mx-auto px-6 w-full grid lg:grid-cols-2 gap-12 items-center">

<!-- LEFT -->
<div>

<h1 class="text-5xl lg:text-[82px] text-[#CEE027] leading-[82px] gilroy font-black font-weight: 700">
  Test, Share & Explore<br>
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

class="relative w-full aspect-square lg:w-[540px] lg:h-[540px]"
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

<section class="bg-[#F5F5F3] py-20">
  <div class="section-wrapper mx-auto grid lg:grid-cols-2 gap-12 items-center">

    <!-- left -->
    <div class="flex flex-col">
    <h1 class="text-5xl lg:text-[82px] dark gilroy leading-[54px] lg:leading-[82px] lg:w-full">
        How to get<br>started<br>in 3 simple<br> steps
      </h1>
      <p class="text-[#1E2330]/70 text-lg max-w-md my-2">
      Join, log, and explore — your season starts with a free account.
      </p>
      <a href="mk/register" class="mt-6 bg-[#1E2330] px-6 py-4 rounded-full font-medium text-white hover:opacity-90 w-auto self-start">
        Create account
      </a>
    </div>

<!-- Right -->
<div class="flex flex-col sm:flex-row gap-6 justify-center">
  <!-- card 1 -->
  <div class="bg-[#DAB6E4] rounded-2xl p-8 w-full sm:w-1/3 flex flex-col items-start">
    <div class="bg-white text-[#1E2330] font-bold w-10 h-10 flex items-center justify-center rounded-full mb-4">
      1
    </div>
    <h3 class="font-semibold text-lg mb-2 leading-[24px]">Create a free account</h3>
    <p class="text-[#1E1E1E]/70 text-sm">
      To get started, simply create your free account to begin your foraging journey.
    </p>
  </div>

  <!-- card 2 -->
  <div class="bg-[#DAB6E4] rounded-2xl p-8 w-full sm:w-1/3 flex flex-col items-start">
    <div class="bg-white text-[#1E1E1E] font-bold w-10 h-10 flex items-center justify-center rounded-full mb-4">
      2
    </div>
    <h3 class="font-semibold text-lg mb-2 leading-[24px]">Start adding adventures</h3>
    <p class="text-[#1E1E1E]/70 text-sm">
      Log your foraging trips, add photos, locations, and notes about your mushroom finds.
    </p>
  </div>

  <!-- card 3 -->
  <div class="bg-[#DAB6E4] rounded-2xl p-8 w-full sm:w-1/3 flex flex-col items-start">
    <div class="bg-white text-[#1E1E1E] font-bold w-10 h-10 flex items-center justify-center rounded-full mb-4">
      3
    </div>
    <h3 class="font-semibold text-lg mb-2 leading-[24px]">Explore your stats</h3>
    <p class="text-[#1E1E1E]/70 text-sm">
      See insights from your season — top harvests, total weight, and your favorite locations.
    </p>
  </div>
</div>

  </div>
</section>

</body>
</html>
