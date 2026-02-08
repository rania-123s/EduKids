<?php

namespace App\Form;

use App\Entity\Cours;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Validator\Constraints\File;

class CoursType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre du cours',
                'attr'  => [
                    'placeholder' => 'Exemple : Mathématiques 6ème année',
                    'class'       => 'form-control',
                ],
                'required' => true,
            ])

            ->add('description', TextareaType::class, [
                'label' => 'Description du cours',
                'attr'  => [
                    'rows'        => 6,
                    'placeholder' => 'Décrivez le contenu, les objectifs, le public cible, les prérequis...',
                    'class'       => 'form-control',
                ],
                'required' => true,
            ])

            ->add('niveau', IntegerType::class, [
                'label' => 'Niveau (ex: 1 = CP, 7 = 1ère année collège, etc.)',
                'attr'  => [
                    'min'   => 1,
                    'max'   => 13,
                    'class' => 'form-control',
                ],
                'required' => true,
            ])
            ->add('matiere', TextType::class, [
                'label' => 'Matière',
                'attr'  => [
                    'placeholder' => 'Exemple : Mathématiques, Français, SVT, Arabe...',
                    'class'       => 'form-control',
                ],
                'required' => true,
            ])
            ->add('image', FileType::class, [
                'label' => 'Image',
                'mapped' => false,  // Important: don't map directly to entity
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'accept' => 'image/*'
                ],
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/jpg',
                            'image/png',
                            'image/gif',
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger une image valide',
                    ])
                ],
            ]);
           
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Cours::class,
        ]);
    }
}