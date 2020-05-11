<form action="<?= PluginEngine::getLink($plugin, [], "process/import") ?>"
      method="post"
      class="default"
      enctype="multipart/form-data">

    <label class="file-upload">
        <?= _("Konfigurationsdatei .flxip hochladen") ?>
        <input type="file" name="file" accept=".flxip,.txt" required>
    </label>

    <div data-dialog-button>
        <?= \Studip\Button::create(_("Hochladen")) ?>
    </div>
</form>
