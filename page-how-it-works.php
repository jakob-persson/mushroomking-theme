<?php
/**
 * Template Name: How It Works
 *
 * A custom page template for the "How It Works" page.
 */
get_header();
?>

<!-- Hero Section -->
<div class="bg-[#124C12] lg:px-16 pb-12">
  <div class="flex flex-col lg:flex-row justify-start lg:justify-center items-start lg:items-center gap-10 lg:min-h-[88vh]">
    <!-- Text Column -->
    <div class="flex-none lg:flex-1 text-left px-6 mb-4 flex flex-col justify-start pt-6 lg:pt-0">
      <h1 class="text-5xl lg:text-[72px] text-[#CEE027] gilroy leading-[54px] lg:leading-[82px] lg:w-full">
        How It Works
      </h1>
      <p class="text-white text-lg max-w-lg my-2">
        Learn how you can track, share, and explore your mushroom foraging adventures through our platform. Here's everything you need to know.
      </p>
    </div>

    <!-- Optional Hero Image -->
    <div class="flex-none lg:flex-1 flex justify-center items-center mt-4 lg:mt-0 hidden lg:flex">
      <img src="<?= get_template_directory_uri(); ?>/images/how-it-works-hero.png" alt="How it works" class="rounded-xl shadow-xl w-full max-w-lg">
    </div>
  </div>
</div>



<
<!-- Section 3: Share With Community -->
<section class="bg-white py-24 px-6 lg:px-16">
  <div class="max-w-4xl mx-auto text-center">
    <h2 class="text-4xl font-bold text-[#1E1E1E] mb-4">Connect & Share</h2>
    <p class="text-[#444] text-lg">
      Share your foraging photos, connect with other mushroom lovers, and explore the community's most recent and impressive finds.
    </p>
  </div>
</section>

<!-- Section 4: Explore By Type -->
<section class="bg-[#1E2330] text-white py-24 px-6 lg:px-16">
  <div class="max-w-4xl mx-auto text-center">
    <h2 class="text-4xl font-bold mb-4">Explore By Mushroom Type</h2>
    <p class="text-lg mb-6">
      Get insights into which types of mushrooms are being harvested most — and where! Know what’s trending in the forest this season.
    </p>
    <!-- Type breakdown table -->
    <div class="bg-white text-black rounded-[30px] p-8 mt-10 max-w-3xl mx-auto">
      <div class="flex items-center justify-between text-xs font-regular mb-4">
        <span>Type / Sort</span>
        <span class="text-right" style="margin-right: 18px">Weight</span>
      </div>
      <div class="space-y-4">
        <?php foreach ($mushroom_types as $m): ?>
          <div class="flex items-center justify-between bg-[#F0F2EF] rounded-2xl p-4">
            <span class="text-sm font-regular"><?= esc_html($m->type) ?></span>
            <span class="text-sm font-medium"><?= rtrim(rtrim(number_format($m->total_kg, 2, '.', ''), '0'), '.') ?>kg</span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

<!-- Call to Action -->
<section class="bg-[#E9C0E9] py-20 text-center px-6">
  <h2 class="text-4xl font-bold text-[#1E1E1E]">Ready to start your mushroom journey?</h2>
  <p class="text-[#1E1E1E] mt-2 text-lg">Join the community and begin logging your first adventure today.</p>
  <a href="<?= esc_url(home_url('/register')) ?>" class="mt-6 inline-block bg-[#1E1E1E] text-white px-6 py-4 rounded-full font-medium hover:opacity-90">
    Create Account
  </a>
</section>

<?php get_footer(); ?>
