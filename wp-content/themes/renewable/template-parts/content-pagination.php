<?php
/**
 * Template part for displaying product pagination in products page
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package catalog_site
 */
 
$max_num_pages = $args['max_num_pages'];

$big = 999999999; // need an unlikely integer
$paginate_links = paginate_links( array(
    'base' => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
    'format' => '?paged=%#%',
    'current' => max( 1, get_query_var('paged') ),
    'total' => $max_num_pages,
    'next_text' => '<span class="page-link"><i class="bi bi-chevron-compact-right"></i></span>',
    'prev_text' => '<span class="page-link"><i class="bi bi-chevron-compact-left"></i></span>',
    'before_page_number' => '<span class="page-link">',
    'after_page_number' => '</span>',
    'type' => 'array'
) );

if( isset ( $paginate_links ) && ! empty ($paginate_links) ) {
?>
<nav aria-label="Page navigation">
    <ul class="pagination mb-0">
        <?php foreach ($paginate_links as $paginate_link) { ?>
        <li class="page-item">
            <?php echo $paginate_link; ?>
        </li>
        <?php } ?>
    </ul>
</nav>
<?php 
}