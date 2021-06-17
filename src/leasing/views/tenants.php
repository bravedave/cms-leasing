<?php
/*
 * David Bray
 * BrayWorth Pty Ltd
 * e. david@brayworth.com.au
 *
 * MIT License
 *
*/

namespace cms\tenants;

use strings;

// \sys::dump( $this->data->tenants, null, false);

?>
<div class="form-row mb-1 d-print-none" id="<?= $srch = strings::rand() ?>envelope">
	<div class="col">
		<input type="search" aria-label="search" class="form-control" autofocus id="<?= $srch ?>">

	</div>

</div>

<div class="table-responsive">
  <table class="table table-sm" id="<?= $tblID = strings::rand() ?>">
    <thead class="small" >
      <tr>
        <td class="text-center" line-number></td>
        <td data-role="sort-header" data-key="name">name</td>
        <td><i class="bi bi-telephone"></i></td>
        <td><i class="bi bi-at"></i></td>
        <td data-role="sort-header" data-key="street_index">address</td>
        <td>start</td>
        <td>end</td>
        <td class="text-center" title="Tenant/Co-Tenant/Approved Occupant">T/C/O</td>
        <td class="text-center">Source</td>

      </tr>

    </thead>

    <tbody>
      <?php while ( $dto = $this->data->tenants->dto()) {
        $street_index = $dto->street_index;
        if ( !$dto->street_index && $dto->address_street) {
          $street_index = strings::street_index( $dto->address_street);

        }
        ?>
        <tr
          data-properties_id="<?= $dto->properties_id ?>"
          data-person_id="<?= $dto->person_id ?>"
          data-name="<?= \htmlspecialchars( $dto->name) ?>"
          data-street="<?= \htmlspecialchars( $dto->address_street) ?>"
          data-street_index="<?= \htmlspecialchars( $street_index) ?>"
          <?php if ( 'console' == $dto->source) print 'class="text-warning"'; ?>
          >
          <td class="small text-center" line-number></td>
          <td>
            <?= $dto->name ?>
          </td>
          <td title="<?= strings::isMobilePhone( $dto->phone) ? strings::asMobilePhone( $dto->phone) : strings::asLocalPhone( $dto->phone) ?>">
            <?= strings::isPhone( $dto->phone) ? '<i class="bi bi-telephone"></i>' : '&nbsp;' ?>
          </td>
          <td title="<?= $dto->email ?>"><?= strings::isEmail( $dto->email) ? '<i class="bi bi-at"></i>' : '&nbsp;' ?></td>
          <td><?= $dto->address_street ?></td>
          <td>
            <?= strings::asLocalDate( $dto->lease_start) ?>
            <div class="text-muted font-italic small"><?= strings::asLocalDate( $dto->lease_start_inaugural) ?></div>

          </td>
          <td><?= strings::asLocalDate( $dto->lease_end) ?></td>
          <td class="text-center"><?= 'tenant' == $dto->type ? 'T' : ( 'cotenant' == $dto->type ? 'C' : ('occupant' == $dto->type ? 'O' : '?')) ?></td>
          <td class="text-center"><?= $dto->source ?></td>

        </tr>
      <?php } ?>

    </tbody>

  </table>

</div>
<script>
( _ => {
  $('#<?= $tblID ?>')
  .on('update-line-numbers', function( e) {
    let tot = 0;
    $('> tbody > tr:not(.d-none) > td[line-number]', this).each( ( i, e) => {
      $(e).data('line', i+1).html( i+1);
      tot ++;

    });

    $('> thead > tr >td[line-number]', this).html(tot);

  });

  $('tbody > tr', '#<?= $tblID ?>').each( (i, tr) => {
    let _tr = $(tr)

    _tr.on( 'contextmenu', function( e) {
      if ( e.shiftKey)
        return;

      e.stopPropagation();e.preventDefault();

      _.hideContexts();

      let _context = _.context();
      let _tr = $(this);
      let _data = _tr.data();

      _context.append(
        $('<a target="_blank"></a>')
        .html( _data.name)
        .prepend('<i class="bi bi-box-arrow-up-right"></i>')
        .attr('href', _.url( 'person/view/' + _data.person_id))
        .on( 'click', function( e) {
          _context.close();

        })

      );

      _context.append(
        $('<a target="_blank"></a>')
        .html( _data.street)
        .prepend('<i class="bi bi-box-arrow-up-right"></i>')
        .attr('href', _.url( 'property/view/' + _data.properties_id))
        .on( 'click', function( e) {
          _context.close();

        })

      );

      _context.open( e);

    });

  });

  $('> tbody > tr > td[line-number]', this).each( ( i, td) => {
    $(td).on( 'click', function( e) {
      e.stopPropagation();

      let _me = $(this);
      let _data = _me.data();
      let _icon = $('i.bi-check', this);
      if ( _icon.length > 0) {
        _me.html( _data.line);

      }
      else {
        _me.html('').append( '<i class="bi bi-check"></i>');

      }

      let selected = $('> tbody > tr > td[line-number] > i.bi-check', '#<?= $tblID ?>');
      if ( selected.length > 0) {
        $('> thead > tr >td[line-number]', '#<?= $tblID ?>').html(selected.length);

      }
      else {
        let n = $('> tbody > tr:not(.d-none) > td[line-number]', this).length;
        $('> thead > tr >td[line-number]', '#<?= $tblID ?>').html(n);

      }

    });

  });

  $(document).ready( () => $('#<?= $tblID ?>').trigger('update-line-numbers'));

	let srchidx = 0;
	let liCutOff = '';
	$('#<?= $srch ?>').on( 'keyup', function( e) {
		let idx = ++srchidx;
		let txt = this.value;

		let _tbl = $('#<?= $tblID ?>');

		$('#<?= $tblID ?> > tbody > tr').each( ( i, tr) => {
			if ( idx != srchidx) return false;

			let _tr = $(tr);
			let _data = _tr.data();

      if ( '' == txt.trim()) {
        _tr.removeClass( 'd-none');

      }
      else {
        let str = _tr.text()
        if ( str.match( new RegExp(txt, 'gi'))) {
          _tr.removeClass( 'd-none');

        }
        else {
          _tr.addClass( 'd-none');

        }

      }

		});

    if ( idx == srchidx) {
      $('#<?= $tblID ?>').trigger( 'update-line-numbers');

    }

	});

})( _brayworth_);
</script>
