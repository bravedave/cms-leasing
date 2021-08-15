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

use strings, theme;

$dto = $this->data->dto;  ?>

<form id="<?= $_form = strings::rand() ?>" autocomplete="off">
  <input type="hidden" name="action" value="property-maintenance-save">
  <input type="hidden" name="id" value="<?= $dto->id ?>">
  <input type="hidden" name="people_id" value="<?= $dto->people_id ?>">

  <div class="modal fade" tabindex="-1" role="dialog" id="<?= $_modal = strings::rand() ?>" aria-labelledby="<?= $_modal ?>Label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header <?= theme::modalHeader() ?>">
          <h5 class="modal-title" id="<?= $_modal ?>Label"><?= $this->title ?></h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">

          <div class="form-row mb-2">
            <div class="col-3 col-form-label">type</div>
            <div class="col">
              <input type="text" name="type" class="form-control" maxlength="42" value="<?= $dto->type ?>">

            </div>

          </div>

          <div class="form-row mb-2">
            <div class="col-3 col-form-label">limit</div>
            <div class="col">
              <input type="text" name="limit" class="form-control" maxlength="42" value="<?= $dto->limit ?>">

            </div>

          </div>

          <div class="form-row mb-2">
            <div class="col-3 col-form-label">property</div>
            <div class="col">
              <select name="properties_id" class="form-control">
                <option value="0" <?= 0 == $dto->properties_id ? 'selected' : '' ?>>all</option>
                <?php
                foreach ($this->data->allProps as $prop) {
                  printf(
                    '<option value="%s" %s>%s</option>',
                    $prop->id,
                    $prop->id == $dto->properties_id ? 'selected' : '',
                    $prop->address_street

                  );

                }

                ?>
              </select>

            </div>

          </div>

          <div class="form-row mb-2">
            <div class="col">
              <textarea name="notes" class="form-control"><?= $dto->notes ?></textarea>

            </div>

          </div>

        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">close</button>
          <button type="submit" class="btn btn-primary">Save</button>
        </div>
      </div>
    </div>
  </div>

  <script>
    (_ => $('#<?= $_modal ?>').on('shown.bs.modal', () => {

      $('textarea', '#<?= $_form ?>')
        .autoResize();

      $('#<?= $_form ?>')
        .on('submit', function(e) {
          let _form = $(this);
          let _data = _form.serializeFormJSON();

          _.post({
            url: _.url('<?= $this->route ?>'),
            data: _data,

          }).then(d => {
            if ('ack' == d.response) {
              $('#<?= $_modal ?>')
                .trigger('success')
                .modal('hide');
            } else {
              _.growl(d);

            }

          });

          return false;
        });
    }))(_brayworth_);
  </script>
</form>