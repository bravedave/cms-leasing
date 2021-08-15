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

use strings, theme;  ?>

<form id="<?= $_form = strings::rand() ?>" autocomplete="off">
  <input type="hidden" name="action" value="">
  <input type="hidden" name="properties_id" value="<?= $this->data->dto->id ?>">

  <div class="modal fade" tabindex="-1" role="dialog" id="<?= $_modal = strings::rand() ?>" aria-labelledby="<?= $_modal ?>Label" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header <?= theme::modalHeader() ?>">
          <h5 class="modal-title" id="<?= $_modal ?>Label"><?= $this->title ?></h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="form-row mb-1">
            <div class="col border-bottom">Type</div>
            <div class="col border-bottom">Limit</div>
            <div class="col-2 border-bottom">Address</div>
            <div class="col-6 border-bottom">Notes</div>
          </div>

          <?php
          // \sys::dump( $this->data->schedule, null, false);
          $console = false;
          foreach ($this->data->schedule as $dto) {
            if ('console' == $dto->source) {
              $console = true;
              print '<div class="form-row mb-2">';
            } else {
              printf(
                '<div class="form-row mb-2"
                  data-id="%s"
                  data-role="maintenance-item">',
                $dto->id

              );
            }

            printf('<div class="col">%s</div>', $dto->type);
            printf('<div class="col">%s</div>', $dto->limit);
            printf(
              '<div class="col-2 text-truncate">%s</div>',
              $dto->properties_id > 0 ? $dto->address_street : ''
            );
            printf('<div class="col-6">%s</div>', $dto->notes);

            print '</div>';
          }
          ?>
        </div>

        <div class="modal-footer">
          <?php if ($console) { ?>
            <button type="button" class="btn btn-outline-secondary" id="<?= $_uid = strings::rand() ?>">import console maintenance</button>
            <script>
              (_ => {
                $('#<?= $_uid ?>')
                  .on('click', function(e) {
                    e.stopPropagation();

                    $('#<?= $_form ?>').trigger('import-from-console');

                  })
              })(_brayworth_);
            </script>
          <?php } else { ?>
            <button type="button" class="btn btn-outline-secondary" id="<?= $_uid = strings::rand() ?>"><i class="bi bi-plus"></i></button>
            <script>
              (_ => {
                $('#<?= $_uid ?>')
                  .on('click', function(e) {
                    e.stopPropagation();

                    $('#<?= $_modal ?>').trigger('add-item');

                  })
              })(_brayworth_);
            </script>
          <?php } ?>
          <button type="button" class="btn btn-outline-secondary ml-auto" data-dismiss="modal">close</button>

        </div>

      </div>
    </div>
  </div>
  <script>
    (_ => {
      let reload = e => _.get.modal(_.url('<?= $this->route ?>/property/<?= $this->data->dto->id ?>'));

      $('#<?= $_modal ?>')
        .on('add-item', function(e) {

          $('#<?= $_modal ?>').modal('hide');

          _.get.modal(_.url('<?= $this->route ?>/add/<?= $this->data->dto->id ?>'))
            .then(m => m.on('hidden.bs.modal', reload));

          console.log('add');

        })
        .on('shown.bs.modal', function(e) {
          $('[data-role="maintenance-item"]', this)
            .each((i, row) => {
              let _row = $(row);

              _row
                .addClass('pointer')
                .on('click', function(e) {
                  e.stopPropagation();
                  e.preventDefault();

                  let _row = $(this);
                  let _data = _row.data();

                  let _modal = $('#<?= $_modal ?>');

                  _modal.modal('hide');

                  _.get.modal(_.url('<?= $this->route ?>/edit/' + _data.id))
                    .then(m => m.on('hidden.bs.modal', reload));

                })
                .on('contextmenu', function(e) {
                  if (e.shiftKey)
                    return;

                  e.stopPropagation();
                  e.preventDefault();

                  _.hideContexts();

                  let _row = $(this);
                  let _context = _.context();

                  _context.append(
                    $('<a href="#">delete</a>')
                    .on('click', function(e) {
                      e.stopPropagation();
                      e.preventDefault();

                      _context.close();
                      _row.trigger('delete');

                    })
                  );

                  _context.open(e);
                })
                .on('delete', function(e) {
                  e.stopPropagation();

                  let _row = $(this);
                  let _data = _row.data();

                  $('#<?= $_modal ?>').modal('hide');

                  _.ask.alert({
                    title: 'Confirm Delete',
                    text: 'Are you sure ?',
                    buttons: {
                      no: function(e) {
                        $(this).modal('hide');
                        reload();

                      },
                      yes: function(e) {
                        $(this).modal('hide');

                        _.post({
                          url: _.url('<?= $this->route ?>'),
                          data: {
                            action: 'property-maintenance-delete',
                            id: _data.id
                          },

                        }).then(d => {
                          if ('ack' != d.response) {
                            _.growl(d);

                          }
                          reload();

                        });

                      }
                    }
                  })

                });

            });

          $('#<?= $_form ?>')
            .on('import-from-console', function(e) {
              let _form = $(this);
              let _data = _form.serializeFormJSON();

              _data.action = 'import-from-console';
              _.post({
                url: _.url('<?= $this->route ?>'),
                data: _data,

              }).then(d => {
                // console.log(d);
                if ('ack' == d.response) {
                  $('#<?= $_modal ?>')
                    .modal('hide');

                  reload();

                } else {
                  _.growl(d);

                }

              });

            })
            .on('submit', function(e) {
              let _form = $(this);
              let _data = _form.serializeFormJSON();

              // console.table( _data);

              return false;
            });
        })
    })(_brayworth_);
  </script>
</form>