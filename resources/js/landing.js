// resources/js/landing.js
function slider() {
  return {
    slides: window.slides || [],
    index: 0,
    gap: 32,
    offset: 0,
    isMoving: true,
    isMobile: false,
    slideSize: 0,
    slideDelay: 3000,
    timer: null,
    isPaused: false,

    isDragging: false,
    dragStartPos: 0,
    dragStartOffset: 0,

    get transformStyle() {
      return this.isMobile
        ? `transform: translateX(${this.offset}px)`
        : `transform: translateY(${this.offset}px)`;
    },

    init() {
      this.$nextTick(() => {
        this.isMobile = window.innerWidth < 1024;

        // duplicate slides for infinite loop
        this.slides = [...this.slides, ...this.slides];

        const slide = this.$refs.wrapper.querySelector("[data-slide]");
        if (!slide) return;

        this.slideSize = this.isMobile
          ? slide.offsetWidth + this.gap
          : slide.offsetHeight + this.gap;

        this.offset = 0;
        this.startAutoplay();

        window.addEventListener(
          "resize",
          () => {
            const wasMobile = this.isMobile;
            this.isMobile = window.innerWidth < 1024;

            // only reset if breakpoint changes
            if (wasMobile !== this.isMobile) {
              this.pause();
              this.index = 0;
              this.offset = 0;

              const slide2 = this.$refs.wrapper.querySelector("[data-slide]");
              if (!slide2) return;

              this.slideSize = this.isMobile
                ? slide2.offsetWidth + this.gap
                : slide2.offsetHeight + this.gap;

              this.resume();
            }
          },
          { passive: true }
        );
      });
    },

    startAutoplay() {
      const run = () => {
        if (this.isPaused) return;

        this.isMoving = true;
        this.offset = -(this.index * this.slideSize);

        const nextIndex = this.index + 1;

        this.timer = setTimeout(() => {
          if (this.isPaused) return;

          if (nextIndex >= this.slides.length / 2) {
            // reset loop
            this.isMoving = false;
            this.index = 0;
            this.offset = 0;

            requestAnimationFrame(() => {
              this.isMoving = true;
              this.timer = setTimeout(() => {
                this.index = 1;
                run();
              }, this.slideDelay);
            });
          } else {
            this.index = nextIndex;
            run();
          }
        }, this.slideDelay);
      };

      run();
    },

    pause() {
      this.isPaused = true;
      clearTimeout(this.timer);
    },

    resume() {
      if (!this.isPaused) return;
      this.isPaused = false;
      this.startAutoplay();
    },

    startDrag(e) {
      this.isDragging = true;
      this.isMoving = false;
      this.$refs.wrapper.setPointerCapture(e.pointerId);

      this.dragStartPos = this.isMobile ? e.clientX : e.clientY;
      this.dragStartOffset = this.offset;

      this.pause();
    },

    onDrag(e) {
      if (!this.isDragging) return;

      const currentPos = this.isMobile ? e.clientX : e.clientY;
      const delta = currentPos - this.dragStartPos;
      const targetOffset = this.dragStartOffset + delta;

      const lerp = (start, end, amt) => start + (end - start) * amt;
      this.offset = lerp(this.offset, targetOffset, 0.2);
    },

    endDrag() {
      if (!this.isDragging) return;

      this.isDragging = false;
      this.isMoving = true;

      const diff = this.offset - this.dragStartOffset;
      const threshold = this.slideSize * 0.1;

      let newIndex = this.index;
      if (diff < -threshold) newIndex = this.index + 1;
      else if (diff > threshold) newIndex = this.index - 1;

      const maxIndex = this.slides.length / 2 - 1;
      newIndex = Math.max(0, Math.min(newIndex, maxIndex));
      this.index = newIndex;

      const targetOffset = -(this.index * this.slideSize);
      const animate = () => {
        this.offset += (targetOffset - this.offset) * 0.2;
        if (Math.abs(this.offset - targetOffset) > 0.5) {
          requestAnimationFrame(animate);
        } else {
          this.offset = targetOffset;
        }
      };
      requestAnimationFrame(animate);

      this.resume();
    },
  };
}

// gör den global så x-data="slider()" hittar funktionen
window.slider = slider;
