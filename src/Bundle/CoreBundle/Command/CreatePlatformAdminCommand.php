<?php

namespace UniteCMS\CoreBundle\Command;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoder;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

use UniteCMS\CoreBundle\Entity\User;

class CreatePlatformAdminCommand extends Command
{
    private $hidePasswordInput = true;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var UserPasswordEncoder
     */
    private $password_encoder;

    /**
     * {@inheritdoc}
     */
    public function __construct(
        EntityManager $em,
        ValidatorInterface $validator,
        UserPasswordEncoderInterface $password_encoder
    ) {
        $this->em = $em;
        $this->validator = $validator;
        $this->password_encoder = $password_encoder;

        parent::__construct();
    }

    /**
     * This function can be called to disable hiding of the password input. This can be useful if this feature is not
     * supported (for example for phpunit tests this can be the case).
     */
    public function disableHidePasswordInput()
    {
        $this->hidePasswordInput = false;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('unite:user:create')
            ->setDescription('Creates a new Platform admin for this installation.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $helper = $this->getHelper('question');

        $question = new Question('<info>Please enter the firstname of the new user:</info> ');
        $firstname = $helper->ask($input, $output, $question);

        $question = new Question('<info>And the lastname:</info> ');
        $lastname = $helper->ask($input, $output, $question);

        $question = new Question('<info>And the email:</info> ');
        $email = $helper->ask($input, $output, $question);

        $question = new Question('<info>Please set a password:</info> ');
        $question->setHidden($this->hidePasswordInput);
        $question->setHiddenFallback(false);
        $password = $helper->ask($input, $output, $question);

        $user = new User();
        $user
            ->setFirstname($firstname)
            ->setLastname($lastname)
            ->setEmail($email)
            ->setRoles([User::ROLE_PLATFORM_ADMIN]);

        $user->setPassword(
            $this->password_encoder->encodePassword(
                $user,
                $password
            )
        );

        $password = null;

        $question = new ConfirmationQuestion(
            '<info>Should the user "'.$user->getFirstname().' '.$user->getFirstname().'" with email "'.$user->getEmail(
            ).'" be created</info>? [<comment>Y/n</comment>] ',
            true,
            '/^(y|j)/i'
        );

        if (!$helper->ask($input, $output, $question)) {
            return;
        }

        $errors = $this->validator->validate($user);
        if (count($errors) == 0) {
            $this->em->persist($user);
            $this->em->flush();
            $output->writeln('<info>Platform Admin was created!</info>');
        } else {
            $output->writeln("<error>\n\nThere was an error while creating the user\n \n$errors\n</error>");
        }
    }
}
