<?php
/**
 * The template for displaying the footer
 *
 * Contains the closing of the #content div and all content after.
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package WordPress
 * @subpackage Twenty_Seventeen
 * @since 1.0
 * @version 1.2
 */

?>

<?php wp_footer(); ?>


<footer class="bg-[#F3F3F1] py-4 px-6">
  <div class="rounded-3xl px-12 py-12 max-w-7xl mx-auto my-8 bg-[#EAB4F8]">
    <div class="flex flex-col md:flex-row justify-between gap-8 text-gray-800 lg:mx-0">
      <!-- Left Columns (Adress & Kontakt) -->
      <div class="flex flex-col md:flex-row gap-8 lg:gap-36 flex-1">

        <div class="hidden lg:block">
            <a href="<?php echo home_url(); ?>">
              <img class="w-12" src="<?php echo get_template_directory_uri(); ?>/images/mk-logo2.png" alt="Logo">
            </a>
        </div>

        <!-- Column 1 -->
        <div>
          <h3 class="font-semibold text-lg mb-4">Pages</h3>
          <div class="space-y-2 text-sm">
          <ul class="space-y-2 text-sm">
              <li><a href="" class="">How it work's</a></li>
              <li><a href="" class="">Get started</a></li>
          </ul>

          </div>
        </div>

        <!-- Column 2 -->
        <div>
          <h3 class="font-semibold text-lg mb-4">Contact</h3>
          <ul class="space-y-2 text-sm">
              <li><a href="mailto:info@mushroomking.se" class="underline">info@mushroomking.se</a></li>
          </ul>
        </div>
      </div>

      <!-- Column 3 (Social Icons) -->
      <div class="flex flex-col lg:items-end">
        <div class="flex gap-8 items-center">
        <div class="flex flex-row gap-4">
          <!-- Facebook -->
          <a href="" target="_blank" class="bg-black rounded-full w-10 h-10 flex items-center justify-center text-white text-xl">
            <i class="fab fa-facebook-f"></i>
          </a>
          <!-- Instagram -->
          <a href="" target="_blank" class="bg-black rounded-full w-10 h-10 flex items-center justify-center text-white text-xl">
            <i class="fab fa-instagram"></i>
          </a>
        </div>
          <div class="block lg:hidden ml-auto">
            <img src="<?php echo get_template_directory_uri(); ?>/images/logo.svg" class="w-32" alt="Logo" class="w-full">
        </div>
        </div>
      </div>

    </div>
  </div>

</footer>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    var ctx = document.getElementById('kgPerMonthChart').getContext('2d');

    // Data from PHP
    var months = <?php echo json_encode($months); ?>;
    var kg_data = <?php echo json_encode($kg_data); ?>;

    // Create the chart
    var kgPerMonthChart = new Chart(ctx, {
      type: 'bar', // Or 'line' for a line chart
      data: {
        labels: months,
        datasets: [{
          label: 'Kilograms Picked',
          data: kg_data,
          backgroundColor: 'rgba(92, 107, 192, 0.2)',
          borderColor: 'rgba(92, 107, 192, 1)',
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              stepSize: 10
            }
          }
        }
      });
  });
</script>
</body>


</html>
