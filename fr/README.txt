$Revision: 331053 $

But : Ce fichier � pour but de d�finir les r�gles � respecter lors de vos traductions/mises � jour de fichiers.

Structure :

I    - Indentation
II   - Coding Standards
III  - Revision tracking
IV   - Commentaires dans les fichiers
V    - Commits et messages de log
VI   - Commandes utiles
VII  - Traductions de quelques mots
VIII - Orthographe et relectures

I - Indentation :

Le pas � respecter pour l'indentation est de 1. Exemple :
<note>
_<para>
__<example>
___<title>
___</title>
__</example>
_</para>
</note>

Le caract�re d'indentation est un espace (aucune tabulation n'est admise dans les fichiers .xml)



II - Coding standards :

Le groupe de documentation PHP a choisi d'utiliser les coding standards de PEAR, vous les trouverez ici : 
  http://pear.php.net/manual/fr/standards.php
Merci donc de les lire et de les appliquer.

Le code source PHP commence � la colonne z�ro de l'exemple :

<?php
ca_commence_ici(); // bien
  ca_commence_ici(); // pas bien
?>

On nottera aussi qu'on privil�gie les echo � print (echo sans parenth�ses).
Tous le code est sens� �tre compatible avec error_reporting(E_ALL) et register_globals = Off.



III - Revision tracking.

Vers la fin f�vrier, la documentation fran�aise a adopt� la m�thode de Revision Tracking par balises :
   http://fr.php.net/manual/howto/translation-revtrack.html (9.4.2).

Dans un premier temps, nous avons rajout� la balise suivante dans tous les fichiers :
<!-- EN-Revision: 1.1 Maintainer: nobody Status: partial -->

Pourquoi avons-nous fais cela ?

L'adoption de cette m�thode permet de mieux suivre les diff�rences entre la 
documentation anglaise et fran�aise. 
Vous pourrez le constater en utilisant le script revcheck.php (dans 
phpdoc-fr/scripts/) ou en visitant http://doc.php.net/php/fr/revcheck.php



IV - Commentaires dans les fichiers

Les seuls commentaires qui doivent figurer en d�but de fichier sont :

<!-- $Revision: 331053 $ -->
<!-- EN-Revision: 1.5 Maintainer: XXXX Status: YYYYY -->
<!-- Reviewed: ZZZ -->

Le dernier tag permet de sp�cifier si le document a �t� relu ou non. ZZZ vaut 'yes' s'il l'a �t�, 'no' sinon.

Et bien s�r, les commentaires des traducteurs (<!-- ne touchez pas ce fichier svp, utilisateur -->)



V - Commits et messages de log

Essayez (dans la mesure du possible) de commiter r�pertoire par r�pertoire.
Dans fr/reference/ commitez extension par extension.

En ce qui concerne les messages de logs pour les commits, on essayera de :
 - faire des messages en anglais (au cas o� jamais un non-francophone a besoin de comprendre les modifications)
 - faire des messages explicites (ne pas mettre "typo" quand on rajoute du texte..)

Bon, �videment, on n'est pas chez les scouts, les �carts seront tol�r�s.



VI - Commandes utiles

(On suppose par la suite qu'on est d'office dans le r�pertoire racine du module de la doc fran�aise)
Voici quelques commandes utiles lors de vos traductions/commits :

1 - Commiter de grosses modifications dans fr/reference/
si vous avez modifi� plusieurs fichiers dans plusieurs extensions :

cd reference
for i in $(ls); do cvs ci -m "message de log" $i; done

2 - Tester syntaxiquement tous les exemples sous fr/reference :

Ici, on va lancer une analyse syntaxique des tous les fichiers dans
les r�pertoires "functions" de fr/reference/. La technique est simple,
on configure short_open_tag � Off en ligne de commande pour que PHP n'analyse
que les exemples commen�ant par <?php, puis on lance la moulinette :

cd reference
for i in $(find -name *.xml); do php -d "short_open_tag=Off" -l $i; done > syntax.txt
cat syntax.txt | grep -B1 Errors



VII  - Traductions de quelques mots

Voir le fichier TRADUCTIONS.txt



VIII - Orthographe et relectures

Afin d'avoir un manuel en bon fran�ais, nous avons des relecteurs.
Les relecteurs ne font presque jamais de traductions (ils ne changent jamais le tag EN-Revision)
Ils doivent par contre changer (ou ajouter) le tag <!-- Reviewed: no/yes -->
Quand un relecteur valide un document, il doit passer la valeur de Reviewed � "yes".
Quand un traducteur met � jour un fichier, il doit passer la valeur de Reviewed � "no".
Nous mettrons bient�t � disposition une interface graphique permettant aux relecteurs de voir les fichiers � valider.
