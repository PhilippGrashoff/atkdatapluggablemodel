<?php declare(strict_types=1);

namespace PhilippR\Atk4\PluggableModel\Tests\TestClasses;

use Atk4\Data\Exception;
use Atk4\Data\Model;
use PhilippR\Atk4\PluggableModel\PluggableModelTrait;

class ModelWithEncryptedField extends Model
{
    use PluggableModelTrait;

    // NOTE: this is only a static demo key for tests; in production, keep the key
    // outside of source code (e.g. environment variable or secret manager)
    private const string ENCRYPTION_KEY = 'my-32-byte-secret-key-for-tests!';

    public $table = 'model_with_extras';

    protected function init(): void
    {
        parent::init();
        $this->addField('name');
        $this->addPluggableFieldsAndHooks();
    }

    protected function getAvailableImplementations(): array
    {
        return [
            ImplementationWithValidation::class => ImplementationWithValidation::$name,
            ImplementationWithEncryption::class => ImplementationWithEncryption::$name,
            ImplementationWithContainsMany::class => ImplementationWithContainsMany::$name,
        ];
    }

    protected function encryptFieldValue(string $fieldName): void
    {
        $value = $this->get($fieldName);
        if ($value === null) {
            return;
        }

        $key = $this->getEncryptionKey();
        // sodium needs string
        $value = (string) $value;
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipher = base64_encode($nonce . sodium_crypto_secretbox($value, $nonce, $key));
        sodium_memzero($value);
        sodium_memzero($key);
        $this->set($fieldName, $cipher);
    }

    protected function decryptFieldValue(string $fieldName): void
    {
        $value = $this->get($fieldName);
        if ($value === null) {
            return;
        }

        $key = $this->getEncryptionKey();
        $decoded = base64_decode((string) $value);
        if (mb_strlen($decoded, '8bit') < (SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES)) {
            throw new Exception('An error occurred decrypting the field value'); // @codeCoverageIgnore
        }
        $nonce = mb_substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, '8bit');
        $ciphertext = mb_substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, null, '8bit');

        $plain = sodium_crypto_secretbox_open($ciphertext, $nonce, $key);
        if ($plain === false) {
            throw new Exception('An error occurred decrypting the field value'); // @codeCoverageIgnore
        }
        sodium_memzero($ciphertext);
        sodium_memzero($key);

        $this->set($fieldName, $plain);
    }

    /**
     * extend this method to get the encryption/decryption key according to your needs
     */
    protected function getEncryptionKey(): string
    {
        return self::ENCRYPTION_KEY;
    }
}