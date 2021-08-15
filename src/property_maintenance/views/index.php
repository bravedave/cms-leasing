<?php
/*
 * David Bray
 * BrayWorth Pty Ltd
 * e. david@brayworth.com.au
 *
 * MIT License
 *
*/

namespace cms\property_maintenance;

use strings;  ?>
<style>
  .toast { z-index: 1050; }
</style>
<h6><a href="<?= strings::url($this->route) ?>"><?= $this->title ?></a></h6>

<ul class="nav flex-column">
  <li class="nav-item"><a class="nav-link" href="#" id="<?= $_uid = strings::rand()  ?>">230D Acacia Drive</a></li>

</ul>
<script>
  (_ => {
    $('#<?= $_uid ?>').on('click', function(e) {
      e.stopPropagation();
      e.preventDefault();

      _.get.modal(_.url('<?= $this->route ?>/property/52728'));

    });

  })(_brayworth_);
</script>