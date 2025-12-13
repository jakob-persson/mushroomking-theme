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

<footer>

</footer>
</body>

</html>
nt.addEventListener('DOMContentLoaded', function () {
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
