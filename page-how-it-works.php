<?php
/**
 * Template Name: How It Works
 */
get_header();
?>

<?php
  // Cards i "Let's go"-sektionen (byt bildnamn efter dina riktiga filer)
  $how_cards = [
    [
      'title' => 'Create account',
      'image' => get_template_directory_uri() . '/images/how-it-works/create-account.png',
    ],
    [
      'title' => 'Create adventure',
      'image' => get_template_directory_uri() . '/images/how-it-works/create-adv.png',
    ],
    [
      'title' => 'Create adventure',
      'image' => get_template_directory_uri() . '/images/how-it-works/create-modal.png',
    ],
    [
      'title' => 'Start view stats',
      'image' => get_template_directory_uri() . '/images/how-it-works/stat.png',
    ],
  ];

  // CTA-botten bild
  $bottom_phone = get_template_directory_uri() . '/images/hiw-bottom-phone.png';

  // Hero-bild
  $hero_image = get_template_directory_uri() . '/images/how-hero.png';
?>

<main class="bg-white">

  <!-- HERO (orange) -->
  <section class="bg-[#FF9313]">
    <div class="section-wrapper py-10 lg:py-14">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-10 items-center">

        <!-- Left -->
        <div class="text-left">
          <h1 class="text-5xl lg:text-[82px] text-[#4C2E05] gilroy leading-[54px] lg:leading-[82px] lg:w-full">
            Get started to<br class="hidden lg:block" />
            track your<br class="hidden lg:block" />
            foraging
          </h1>

          <p class="mt-4 text-[#1B1B1B]/85 text-base lg:text-lg max-w-xl">
            Start shared tracking, view statistics and adventures.
          </p>

          <a
            href="<?= esc_url(home_url('/how-it-works')); ?>"
            class="inline-flex mt-6 items-center justify-center rounded-full bg-[#1B1B1B] text-white px-6 py-3 text-sm font-medium hover:opacity-90"
          >
            How it works
          </a>
        </div>

        <!-- Right (image) -->
        <div class="flex justify-center lg:justify-end">
          <div class="rounded-3xl overflow-hidden px-12">
            <img
              src="<?= esc_url($hero_image); ?>"
              alt="How it works hero"
              class="block w-full max-w-[560px] h-auto"
              loading="lazy"
            />
          </div>
        </div>

      </div>
    </div>
  </section>

  <!-- LET'S GO -->
<section class="bg-|#F3F3F1]">
  <div class="section-wrapper py-16 lg:py-20"
       x-data="howItWorksNav"
       x-init="init()">

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-10">

      <!-- Left column (sticky + menu) -->
      <div class="lg:col-span-4">
        <div class="lg:sticky lg:top-[140px]">
          <h2 class="gilroy text-[#1E2330] text-4xl lg:text-6xl font-black tracking-tight">
            Let's get started.
          </h2>

          <ol class="mt-8  text-[#1E2330]">
            <template x-for="item in items" :key="item.id">
              <li>
                <button
                type="button"
                @click="scrollTo(item.id)"
                class="w-full text-left flex items-center gap-3 transition"
              >
                <span
                  class="inline-flex items-center rounded-lg px-3 py-3 transition"
                  :class="activeId === item.id
                    ? 'font-semibold'
                    : 'hover:text-[#1E2330] text-[#1E2330]/50'"
                >
                  <span class="font-semibold w-6" x-text="item.num + '.'"></span>

                  <span
                    :class="activeId === item.id
                      ? 'text-[#1E2330]'
                      : 'text-[#1E2330]/50'"
                    x-text="item.label"
                  ></span>
                </span>
              </button>

              </li>
            </template>
          </ol>

          <div class="mt-10">
            <a
              href="<?= esc_url(home_url('/register')); ?>"
              class="inline-flex items-center justify-center rounded-full bg-[#1E2330] text-white px-6 py-3 text-sm font-medium hover:opacity-90"
            >
              Create account
            </a>
          </div>
        </div>
      </div>


     <!-- Right column (cards) -->
      <div class="lg:col-span-8">
        <div class="space-y-8">
          <?php foreach ($how_cards as $i => $card):
        
            $id = '';
            if ($i === 0) $id = 'step-create-account';
            if ($i === 1) $id = 'step-add-adventure';
            if ($i === 2) $id = 'step-view-stats';
            if ($i === 3) $id = 'step-share';
          ?>
            <article
              id="<?= esc_attr($id); ?>"
              data-hiw-section="true"
              class="rounded-3xl scroll-mt-[160px]"
            >
              <div class="flex justify-center">
                <img
                  src="<?= esc_url($card['image']); ?>"
                  alt=""
                  class="w-full  h-auto"
                  loading="lazy"
                  decoding="async"
                />
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </div>

    </div>
  </div>
</section>

<script>
document.addEventListener('alpine:init', () => {
  Alpine.data('howItWorksNav', () => ({
    items: [
      { id: 'step-create-account', num: 1, label: 'Create account' },
      { id: 'step-add-adventure',  num: 2, label: 'Add adventure' },
      { id: 'step-view-stats',     num: 3, label: 'View stats' },
      { id: 'step-share',          num: 4, label: 'Share your adventures with others' },
    ],

    activeId: 'step-create-account',
    observer: null,
    isJumping: false,
    jumpTimer: null,
    raf: null,

    init() {
      // ✅ vänta tills DOM är klar
      this.$nextTick(() => {
        this.activeId = this.items[0]?.id || null;

        const sections = this.items
          .map(i => document.getElementById(i.id))
          .filter(Boolean);

        if (!sections.length) return;

        // ✅ IntersectionObserver (primär)
        if ('IntersectionObserver' in window) {
          this.observer = new IntersectionObserver((entries) => {
            if (this.isJumping) return;

            const visible = entries
              .filter(e => e.isIntersecting)
              .sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top);

            if (visible[0]?.target?.id) {
              this.activeId = visible[0].target.id;
            }
          }, {
            root: null,
            rootMargin: "-160px 0px -55% 0px",
            threshold: 0.01,
          });

          sections.forEach(sec => this.observer.observe(sec));
        }

        const onScroll = () => {
          if (this.isJumping) return;

          cancelAnimationFrame(this.raf);
          this.raf = requestAnimationFrame(() => {
            const offset = 160;
            let bestId = this.activeId;
            let bestDist = Infinity;

            sections.forEach(sec => {
              const top = sec.getBoundingClientRect().top - offset;
              const dist = Math.abs(top);
              if (top <= window.innerHeight && dist < bestDist) {
                bestDist = dist;
                bestId = sec.id;
              }
            });

            if (bestId) this.activeId = bestId;
          });
        };

        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();
      });
    },

    scrollTo(id) {
      const el = document.getElementById(id);
      if (!el) return;

      this.activeId = id;

      this.isJumping = true;
      clearTimeout(this.jumpTimer);

      el.scrollIntoView({ behavior: 'smooth', block: 'start' });

      this.jumpTimer = setTimeout(() => {
        this.isJumping = false;
      }, 700);
    },
  }));
});
</script>




  <!-- Bottom CTA card -->
  <section class="bg-white pb-20">
    <div class="section-wrapper">
      <div class="grid grid-cols-1 lg:grid-cols-2 rounded-3xl overflow-hidden">

        <!-- Left dark -->
        <div class="bg-[#1B1B1B] p-10 lg:p-12 relative">
          <div class="text-[#CEE027] font-black tracking-tight">
            MUSHROOMKING
          </div>

          <p class="mt-8 text-white/80 max-w-md leading-relaxed">
            Built for designers, sellers, and businesses bringing brands, products, and ideas to life.
          </p>

          <div class="mt-8">
            <a
              href="<?= esc_url(home_url('/register')); ?>"
              class="inline-flex items-center justify-center rounded-full bg-white text-[#1B1B1B] px-6 py-3 text-sm font-medium hover:opacity-90"
            >
              Create account
            </a>
          </div>
        </div>

        <!-- Right green with phone -->
        <div class="bg-[#124C12] p-10 lg:p-12 flex items-center justify-center">
          <img
            src="<?= esc_url($bottom_phone); ?>"
            alt="App preview"
            class="w-full max-w-[380px] h-auto"
            loading="lazy"
          />
        </div>

      </div>
    </div>
  </section>

</main>

<?php get_footer(); ?>
