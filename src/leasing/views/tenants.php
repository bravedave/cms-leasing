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
    <thead class="small">
      <tr>
        <td class="text-center" line-number></td>
        <td data-role="sort-header" data-key="name">name</td>
        <td><i class="bi bi-telephone"></i></td>
        <td><i class="bi bi-at"></i></td>
        <td data-role="sort-header" data-key="street_index">address</td>
        <td>start</td>
        <td>end</td>
        <td data-role="sort-header" data-key="tenant_type" class="text-center" title="Tenant/Co-Tenant/Approved Occupant/Guarantor">T/C/O/G</td>
        <td class="text-center">Source</td>
        <td class="text-center" PM>PM</td>

      </tr>

    </thead>

    <tbody>
      <?php while ($dto = $this->data->tenants->dto()) {
        $street_index = $dto->street_index;
        if (!$dto->street_index && $dto->address_street) {
          $street_index = strings::street_index($dto->address_street);
        }
        $type = '?';
        if ('tenant' == $dto->type) {
          $type = 'T';
        } elseif ('cotenant' == $dto->type) {
          $type = 'C';
        } elseif ('occupant' == $dto->type) {
          $type = 'O';
        } elseif ('guarantor' == $dto->type) {
          $type = 'G';
        }

        printf(
          '<tr
            data-properties_id="%s"
            data-person_id="%s"
            data-name="%s"
            data-hasemail="%s"
            data-street="%s"
            data-street_index="%s"
            data-tenant_type="%s"
            data-pm="%s"
            class="%s">',
            $dto->properties_id,
            $dto->person_id,
            htmlspecialchars($dto->name),
            strings::isEmail($dto->email) ? 'yes' : 'no',
            htmlspecialchars($dto->address_street),
            htmlspecialchars($street_index),
            $type,
            strings::initials($dto->property_manager_name),
            'console' == $dto->source ? 'text-warning' : ''

        );
      ?>

          <td class="small text-center" line-number></td>
          <td>
            <?= $dto->name ?>
          </td>
          <td title="<?= strings::isMobilePhone($dto->phone) ? strings::asMobilePhone($dto->phone) : strings::asLocalPhone($dto->phone) ?>">
            <?= strings::isPhone($dto->phone) ? '<i class="bi bi-telephone"></i>' : '&nbsp;' ?>
          </td>
          <td title="<?= $dto->email ?>"><?= strings::isEmail($dto->email) ? '<i class="bi bi-at"></i>' : '&nbsp;' ?></td>
          <td><?= $dto->address_street ?></td>
          <td>
            <?= strings::asLocalDate($dto->lease_start) ?>
            <div class="text-muted font-italic small"><?= strings::asLocalDate($dto->lease_start_inaugural) ?></div>

          </td>
          <td>
            <?= strings::asLocalDate($dto->lease_end) ?>
            <?php
              if ( strtotime( $dto->vacate) > 0) {
                printf(
                  '<div class="text-muted font-italic small">%s</div>',
                  strings::asLocalDate($dto->vacate)

                );

              }
              elseif ( strtotime( $dto->vacate_console) > 0) {
                printf(
                  '<div class="font-italic small text-warning" title="console data">%s</div>',
                  strings::asLocalDate($dto->vacate_console)

                );

              }
            ?>
          </td>
          <td class="text-center"><?= $type ?></td>
          <td class="text-center"><?= $dto->source ?></td>
          <td class="text-center"><?= strings::initials( $dto->property_manager_name) ?></td>


      <?php
      print '</tr>';
    } ?>

    </tbody>

  </table>

</div>
<script>
  (_ => {
    $('#<?= $tblID ?>')
      .on('update-line-numbers', function(e) {
        let tot = 0;
        $('> tbody > tr:not(.d-none) > td[line-number]', this).each((i, e) => {
          $(e).data('line', i + 1).html(i + 1);
          tot++;

        });

        $('> thead > tr >td[line-number]', this).html(tot);

      });

    let pms = [];
    $('tbody > tr', '#<?= $tblID ?>')
      .each((i, tr) => {
        let _tr = $(tr)
        let _data = _tr.data();

        if ('' != String(_data.pm)) {
          if (pms.indexOf(String(_data.pm)) < 0) {
            pms.push(String(_data.pm));

          }

        }

        _tr
          .on('contextmenu', function(e) {
            if (e.shiftKey)
              return;

            e.stopPropagation();
            e.preventDefault();

            _.hideContexts();

            let _context = _.context();
            let _tr = $(this);
            let _data = _tr.data();

            _context.append(
              $('<a target="_blank"></a>')
              .html(_data.name)
              .prepend('<i class="bi bi-box-arrow-up-right"></i>')
              .attr('href', _.url('person/view/' + _data.person_id))
              .on('click', function(e) {
                _context.close();

              })

            );

            _context.append(
              $('<a target="_blank"></a>')
              .html(_data.street)
              .prepend('<i class="bi bi-box-arrow-up-right"></i>')
              .attr('href', _.url('property/view/' + _data.properties_id))
              .on('click', function(e) {
                _context.close();

              })

            );

            _context.open(e);

          });

      });

    $('> tbody > tr[data-hasemail="yes"] > td[line-number]', '#<?= $tblID ?>')
      .each((i, td) => {
        $(td)
          .addClass('pointer')
          .attr('title', 'click to select')
          .on('click', function(e) {
            e.stopPropagation();

            let _me = $(this);
            let _data = _me.data();
            let _icon = $('i.bi-check', this);

            _me.html(_icon.length > 0 ? _data.line : '<i class="bi bi-check"></i>');

            $('#<?= $tblID ?>').trigger('total-selected');

          });

      });

    $('#<?= $tblID ?>')
      .on('mailout', function(e) {
        let _form = $('<form method="post" action="<?= strings::url('email/bulk') ?>"></form>');

        let cids = [];
        let tot = 0;
        $('> tbody > tr > td[line-number] > i.bi-check', '#<?= $tblID ?>')
          .each((i, el) => {
            let _tr = $(el).closest('tr');
            let _data = _tr.data();

            if (Number(_data.person_id) > 0) {
              if (cids.indexOf(_data.person_id) < 0) {
                cids.push(_data.person_id);

                $('<input type="hidden" name="contactID[]">')
                  .attr('value', _data.person_id)
                  .appendTo(_form);

                tot++;

              }

            }

          });

        if (tot > 0) {
          _form
            .appendTo('body')
            .submit();

        } else {
          _.growl('nothing selected');

        }

      })
      .on('select-all', function(e) {

        $('> tbody > tr[data-hasemail="yes"]:not(.d-none) > td[line-number]', this)
          .each((i, td) => $(td).html('').append('<i class="bi bi-check"></i>'));

        $(this).trigger('total-selected');

      })
      .on('total-selected', function(e) {

        let _me = $(this);

        let n = $('> tbody > tr > td[line-number] > i.bi-check', '#<?= $tblID ?>').length;
        if (n > 0) {
          n = (n => {
            return $('<div class="badge badge-primary"></div>')
              .html(n)
              .on('contextmenu', function(e) {
                if (e.shiftKey)
                  return;

                e.stopPropagation();
                e.preventDefault();

                _.hideContexts();

                let _context = _.context();

                _context.append($('<a href="#">Bulk Mail</a>').on('click', function(e) {
                  e.stopPropagation();
                  e.preventDefault();

                  _context.close();

                  _me.trigger('mailout');

                }));

                _context.append($('<a href="#">select none</a>').on('click', function(e) {
                  e.stopPropagation();
                  e.preventDefault();

                  _context.close();
                  $('#<?= $tblID ?>').trigger('update-line-numbers');

                }));

                _context.open(e);

              });;

          })(n);

        } else {
          n = $('> tbody > tr:not(.d-none) > td[line-number]', '#<?= $tblID ?>').length;

        }

        $('> thead > tr >td[line-number]', '#<?= $tblID ?>').html(n);

      });

    $('> thead > tr >td[line-number]', '#<?= $tblID ?>')
      .on('contextmenu', function(e) {
        if (e.shiftKey)
          return;

        e.stopPropagation();
        e.preventDefault();

        _.hideContexts();

        let _context = _.context();

        _context.append($('<a href="#">select all</a>').on('click', function(e) {
          e.stopPropagation();
          e.preventDefault();

          _context.close();
          $('#<?= $tblID ?>').trigger('select-all');

        }));

        _context.append($('<a href="#">select none</a>').on('click', function(e) {
          e.stopPropagation();
          e.preventDefault();

          _context.close();
          $('#<?= $tblID ?>').trigger('update-line-numbers');

        }));

        _context.open(e);

      });

    let filterPM = '';
    if (pms.length > 0) {
      $('#<?= $tblID ?> > thead > tr > td[PM]')
        .on(_.browser.isMobileDevice ? 'click' : 'contextmenu', function(e) {
          if (e.shiftKey)
            return;

          e.stopPropagation();
          e.preventDefault();

          _.hideContexts();

          let _context = _.context();
          let _me = $(this);

          $.each(pms, (i, pm) => {
            _context.append(
              $('<a href="#"></a>')
              .html(pm)
              .on('click', function(e) {
                e.stopPropagation();
                e.preventDefault();
                _context.close();

                filterPM = $(this).html();
                _me
                  .html('')
                  .append($('<div class="badge badge-primary"></div>').html(filterPM));

                $('#<?= $srch ?>').trigger('search');
                localStorage.setItem('properties-forrent-filter-pm', filterPM);

              })
              .on('reconcile', function() {
                if (pm == filterPM) $(this).prepend('<i class="bi bi-check"></i>')

              })
              .trigger('reconcile')

            );

          });

          _context.append('<hr>');
          _context.append(
            $('<a href="#">clear</a>').on('click', function(e) {
              e.stopPropagation();
              e.preventDefault();
              _context.close();

              filterPM = '';
              _me.html('PM');
              $('#<?= $srch ?>')
                .trigger('search');

              localStorage.removeItem('properties-forrent-filter-pm');

            })
          );

          _context.open(e);

        });

    }

    let filterTenantType = '';
    $('> thead > tr >td[data-key="tenant_type"]', '#<?= $tblID ?>')
      .on('contextmenu', function(e) {
        if (e.shiftKey)
          return;

        e.stopPropagation();
        e.preventDefault();
        _.hideContexts();

        let _me = $(this);
        let _context = _.context();

        // T / C / O / G
        _context.append(
          $('<a href="#">Tenant</a>')
          .on('click', function(e) {
            e.stopPropagation();
            e.preventDefault();

            _context.close();
            filterTenantType = 'T';
            _me.html('<div class="badge badge-primary">T</div>');
            $('#<?= $srch ?>').trigger('search');

          })
          .on('reconcile', function(e) {
            if ('T' == filterTenantType)
              $(this).prepend('<i class="bi bi-check"></i>')
          })
          .trigger('reconcile')
        );

        _context.append(
          $('<a href="#">Co-Tenant</a>')
          .on('click', function(e) {
            e.stopPropagation();
            e.preventDefault();

            _context.close();
            filterTenantType = 'C';
            _me.html('<div class="badge badge-primary">C</div>');
            $('#<?= $srch ?>').trigger('search');

          })
          .on('reconcile', function(e) {
            if ('C' == filterTenantType)
              $(this).prepend('<i class="bi bi-check"></i>')
          })
          .trigger('reconcile')
        );

        _context.append(
          $('<a href="#">Approved Occupant</a>')
          .on('click', function(e) {
            e.stopPropagation();
            e.preventDefault();

            _context.close();
            filterTenantType = 'O';
            _me.html('<div class="badge badge-primary">O</div>');
            $('#<?= $srch ?>').trigger('search');

          })
          .on('reconcile', function(e) {
            if ('O' == filterTenantType)
              $(this).prepend('<i class="bi bi-check"></i>')
          })
          .trigger('reconcile')
        );

        _context.append(
          $('<a href="#">Guarantor</a>')
          .on('click', function(e) {
            e.stopPropagation();
            e.preventDefault();

            _context.close();
            filterTenantType = 'G';
            _me.html('<div class="badge badge-primary">G</div>');
            $('#<?= $srch ?>').trigger('search');

          })
          .on('reconcile', function(e) {
            if ('G' == filterTenantType)
              $(this).prepend('<i class="bi bi-check"></i>')
          })
          .trigger('reconcile')
        );

        _context.append('<hr>');
        _context.append(
          $('<a href="#">clear</a>')
          .on('click', function(e) {
            e.stopPropagation();
            e.preventDefault();

            _context.close();
            filterTenantType = '';
            _me.html('T/C/O/G');
            $('#<?= $srch ?>').trigger('search');

          })
        );

        _context.open(e);

      });

    $(document).ready(() => $('#<?= $tblID ?>').trigger('update-line-numbers'));

    let srchidx = 0;
    let liCutOff = '';
    $('#<?= $srch ?>')
      .on('keyup', function(e) {
        $(this).trigger('search')
      })
      .on('search', function(e) {
        let idx = ++srchidx;
        let txt = this.value;

        let _tbl = $('#<?= $tblID ?>');

        $('#<?= $tblID ?> > tbody > tr')
          .each((i, tr) => {
            if (idx != srchidx) return false;

            let _tr = $(tr);
            let _data = _tr.data();

            if (filterPM != '' && _data.pm != filterPM) {
              _tr.addClass('d-none');

            } else if ('' != filterTenantType && _data.tenant_type != filterTenantType) {
              _tr.addClass('d-none');

            } else if ('' == txt.trim()) {
              _tr.removeClass('d-none');

            } else {
              let str = _tr.text()
              if (str.match(new RegExp(txt, 'gi'))) {
                _tr.removeClass('d-none');

              } else {
                _tr.addClass('d-none');

              }

            }

          });

        if (idx == srchidx) {
          $('#<?= $tblID ?>').trigger('update-line-numbers');

        }

      });

  })(_brayworth_);
</script>